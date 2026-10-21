#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use std::fs::{self, File, OpenOptions};
use std::hash::{DefaultHasher, Hash, Hasher};
use std::net::TcpListener;
use std::path::{Path, PathBuf};
use std::process::{Child, Command, Stdio};
use std::sync::Mutex;
use std::thread;
use std::time::{Duration, Instant};

use serde::Deserialize;
use tauri::menu::{IsMenuItem, Menu, MenuItem, PredefinedMenuItem};
use tauri::tray::TrayIconBuilder;
use tauri::{
    AppHandle, Manager, RunEvent, Url, WebviewUrl, WebviewWindowBuilder, WindowEvent, Wry,
};
#[cfg(not(target_os = "linux"))]
use tauri_plugin_notification::NotificationExt;

type Failure = Box<dyn std::error::Error + Send + Sync>;

const WINDOW: &str = "main";
const TRAY: &str = "main";
const OPEN: &str = "open";
const QUIT: &str = "quit";
const SECRET_HEADER: &str = "X-Lorekeeper-Secret";
const POLL_EVERY: Duration = Duration::from_secs(3);
const PATIENCE: Duration = Duration::from_secs(30);
const ICON: &[u8] = include_bytes!("../icons/128x128.png");

struct Server(Mutex<Option<Child>>);

struct Php {
    binary: PathBuf,
    app: PathBuf,
    data: PathBuf,
    env: Vec<(String, String)>,
}

#[derive(Deserialize)]
struct Poll {
    bots: Vec<String>,
    notices: Vec<Notice>,
    reload: bool,
}

#[derive(Deserialize)]
struct Notice {
    title: String,
    message: String,
}

fn main() {
    let builder = tauri::Builder::default()
        .plugin(tauri_plugin_single_instance::init(|app, _, _| show(app)))
        .plugin(tauri_plugin_window_state::Builder::default().build());
    #[cfg(not(target_os = "linux"))]
    let builder = builder.plugin(tauri_plugin_notification::init());

    builder
        .manage(Server(Mutex::new(None)))
        .setup(|app| {
            WebviewWindowBuilder::new(app, WINDOW, WebviewUrl::App("index.html".into()))
                .title("Lorekeeper")
                .inner_size(1280.0, 820.0)
                .min_inner_size(900.0, 600.0)
                .build()?;
            tray(app.handle(), &[])?;
            #[cfg(unix)]
            on_signals(app.handle())?;

            let handle = app.handle().clone();
            thread::spawn(move || {
                if let Err(failure) = start(&handle) {
                    fail(&handle, &failure.to_string());
                }
            });

            Ok(())
        })
        .on_window_event(|window, event| {
            if let WindowEvent::CloseRequested { api, .. } = event {
                let _ = window.hide();
                api.prevent_close();
            }
        })
        .build(tauri::generate_context!())
        .expect("the shell could not be built")
        .run(|app, event| {
            if let RunEvent::Exit = event {
                stop(app);
            }
        });
}

fn start(app: &AppHandle) -> Result<(), Failure> {
    let port = TcpListener::bind("127.0.0.1:0")?.local_addr()?.port();
    let secret = secret()?;
    let url = format!("http://127.0.0.1:{port}");
    let php = Php::locate(app, &url, &secret)?;
    php.reap();
    let icon = php.data.join("icon.png");
    fs::write(&icon, ICON)?;

    let boot = php.command(&["artisan", "desktop:boot"]).output()?;
    if !boot.status.success() {
        return Err(format!(
            "desktop:boot failed\n{}{}",
            String::from_utf8_lossy(&boot.stdout),
            String::from_utf8_lossy(&boot.stderr)
        )
        .into());
    }

    let server = php.serve(port)?;
    app.state::<Server>().0.lock().unwrap().replace(server);
    wait_until_up(&url)?;
    open(app, &url)?;

    watch(app, &url, &secret, &format!("file://{}", path(&icon)));

    Ok(())
}

impl Php {
    fn locate(app: &AppHandle, url: &str, secret: &str) -> Result<Self, Failure> {
        let (binary, code) = if cfg!(debug_assertions) {
            (
                PathBuf::from("php"),
                PathBuf::from(env!("CARGO_MANIFEST_DIR")).join(".."),
            )
        } else {
            let resources = app.path().resource_dir()?;
            (resources.join("php").join("php"), resources.join("app"))
        };
        let binary = std::env::var_os("LOREKEEPER_PHP")
            .map(PathBuf::from)
            .unwrap_or(binary);
        let code = fs::canonicalize(
            std::env::var_os("LOREKEEPER_APP")
                .map(PathBuf::from)
                .unwrap_or(code),
        )?;

        let data = if cfg!(debug_assertions) {
            app.path().app_data_dir()?.join("dev")
        } else {
            app.path().app_data_dir()?
        };
        let storage = data.join("storage");
        for folder in [
            "app",
            "framework/cache/data",
            "framework/sessions",
            "framework/views",
            "logs",
        ] {
            fs::create_dir_all(storage.join(folder))?;
        }
        refresh_bootstrap_cache(
            &data.join("bootstrap/cache"),
            &code,
            &app.package_info().version.to_string(),
        )?;

        let database = data.join("database.sqlite");
        OpenOptions::new()
            .create(true)
            .append(true)
            .open(&database)?;

        let ini = data.join("php");
        fs::create_dir_all(&ini)?;
        fs::write(
            ini.join("lorekeeper.ini"),
            "memory_limit = 512M\nvariables_order = \"EGPCS\"\nopcache.enable = 1\n",
        )?;

        let env = vec![
            (
                "APP_ENV",
                if cfg!(debug_assertions) {
                    "local"
                } else {
                    "production"
                }
                .to_string(),
            ),
            ("APP_DEBUG", cfg!(debug_assertions).to_string()),
            ("APP_URL", url.to_string()),
            ("LOREKEEPER_SHELL", "tauri".to_string()),
            ("LOREKEEPER_SHELL_SECRET", secret.to_string()),
            ("LOREKEEPER_DATA_PATH", path(&data)),
            ("LARAVEL_STORAGE_PATH", path(&storage)),
            ("DB_CONNECTION", "sqlite".to_string()),
            ("DB_DATABASE", path(&database)),
            ("PHP_INI_SCAN_DIR", format!(":{}", path(&ini))),
            ("PHP_CLI_SERVER_WORKERS", "2".to_string()),
        ];

        Ok(Self {
            binary,
            app: code,
            data,
            env: env
                .into_iter()
                .map(|(key, value)| (key.to_string(), value))
                .collect(),
        })
    }

    fn command(&self, args: &[&str]) -> Command {
        let mut command = Command::new(&self.binary);
        command
            .args(args)
            .current_dir(&self.app)
            .envs(self.env.iter().cloned());
        command
    }

    fn serve(&self, port: u16) -> Result<Child, Failure> {
        let router = self
            .app
            .join("vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php");
        let log = File::create(self.data.join("storage/logs/server.log"))?;
        let mut command = self.command(&["-S", &format!("127.0.0.1:{port}"), &path(&router)]);
        command
            .current_dir(self.app.join("public"))
            .stdout(Stdio::null())
            .stderr(log);

        #[cfg(unix)]
        std::os::unix::process::CommandExt::process_group(&mut command, 0);
        #[cfg(target_os = "linux")]
        dies_with_parent(&mut command);

        let server = command.spawn()?;
        fs::write(self.group_file(), server.id().to_string())?;

        Ok(server)
    }

    fn reap(&self) {
        let file = self.group_file();
        let group = fs::read_to_string(&file)
            .ok()
            .and_then(|text| text.trim().parse::<u32>().ok());
        #[cfg(unix)]
        if let Some(group) = group.filter(|group| php_in_group(*group)) {
            unsafe {
                libc::killpg(group as libc::pid_t, libc::SIGTERM);
            }
        }
        #[cfg(not(unix))]
        let _ = group;
        let _ = fs::remove_file(file);
    }

    fn group_file(&self) -> PathBuf {
        self.data.join("server.pgid")
    }
}

#[cfg(target_os = "linux")]
fn dies_with_parent(command: &mut Command) {
    unsafe {
        std::os::unix::process::CommandExt::pre_exec(command, || {
            libc::prctl(libc::PR_SET_PDEATHSIG, libc::SIGTERM);
            Ok(())
        });
    }
}

#[cfg(unix)]
fn php_in_group(group: u32) -> bool {
    let Ok(processes) = fs::read_dir("/proc") else {
        return false;
    };

    processes.flatten().any(|process| {
        let stat = fs::read_to_string(process.path().join("stat")).unwrap_or_default();
        let in_group = stat
            .rsplit_once(") ")
            .and_then(|(_, fields)| fields.split(' ').nth(2))
            .is_some_and(|field| field == group.to_string());
        let command = fs::read(process.path().join("cmdline")).unwrap_or_default();

        in_group && String::from_utf8_lossy(&command).contains("php")
    })
}

#[cfg(unix)]
fn on_signals(app: &AppHandle) -> std::io::Result<()> {
    use signal_hook::consts::{SIGHUP, SIGINT, SIGTERM};

    let mut signals = signal_hook::iterator::Signals::new([SIGTERM, SIGINT, SIGHUP])?;
    let handle = app.clone();
    thread::spawn(move || {
        if signals.forever().next().is_some() {
            handle.exit(0);
        }
    });

    Ok(())
}

fn wait_until_up(url: &str) -> Result<(), Failure> {
    let since = Instant::now();
    while since.elapsed() < PATIENCE {
        if ureq::get(&format!("{url}/up")).call().is_ok() {
            return Ok(());
        }
        thread::sleep(Duration::from_millis(150));
    }

    Err("the PHP server did not answer in time".into())
}

fn open(app: &AppHandle, url: &str) -> Result<(), Failure> {
    let Some(window) = app.get_webview_window(WINDOW) else {
        return Ok(());
    };
    if on_app(&window.url()?, url) {
        return Ok(());
    }

    eprintln!(
        "lorekeeper: the window is on {}, opening {url}",
        window.url()?
    );
    window.navigate(Url::parse(&format!("{url}/"))?)?;

    Ok(())
}

fn on_app(current: &Url, url: &str) -> bool {
    current.as_str().starts_with(&format!("{url}/"))
}

fn watch(app: &AppHandle, url: &str, secret: &str, icon: &str) {
    let mut shown: Vec<String> = Vec::new();
    loop {
        if let Err(failure) = open(app, url) {
            eprintln!("lorekeeper: could not open the app: {failure}");
        }

        let poll = ureq::get(&format!("{url}/shell"))
            .set(SECRET_HEADER, secret)
            .call()
            .map_err(Failure::from)
            .and_then(|response| response.into_json::<Poll>().map_err(Failure::from));

        if let Ok(poll) = poll {
            if poll.bots != shown {
                let handle = app.clone();
                let bots = poll.bots.clone();
                let _ = app.run_on_main_thread(move || {
                    let _ = tray(&handle, &bots);
                });
                shown = poll.bots;
            }

            for notice in poll.notices {
                notify(app, icon, notice);
            }

            if poll.reload {
                if let Some(window) = app.get_webview_window(WINDOW) {
                    let _ = window.eval("location.reload()");
                }
            }
        }

        thread::sleep(POLL_EVERY);
    }
}

#[cfg(target_os = "linux")]
fn notify(_: &AppHandle, icon: &str, notice: Notice) {
    let _ = notify_rust::Notification::new()
        .appname("Lorekeeper")
        .summary(&notice.title)
        .body(&notice.message)
        .image_path(icon)
        .show();
}

#[cfg(not(target_os = "linux"))]
fn notify(app: &AppHandle, _: &str, notice: Notice) {
    let _ = app
        .notification()
        .builder()
        .title(notice.title)
        .body(notice.message)
        .show();
}

fn tray(app: &AppHandle, bots: &[String]) -> tauri::Result<()> {
    let open = MenuItem::with_id(app, OPEN, "Open Lorekeeper", true, None::<&str>)?;
    let quit = MenuItem::with_id(app, QUIT, "Quit and stop the bots", true, None::<&str>)?;
    let top = PredefinedMenuItem::separator(app)?;
    let bottom = PredefinedMenuItem::separator(app)?;
    let labels = bots
        .iter()
        .enumerate()
        .map(|(index, label)| {
            MenuItem::with_id(app, format!("bot-{index}"), label, false, None::<&str>)
        })
        .collect::<tauri::Result<Vec<_>>>()?;

    let mut items: Vec<&dyn IsMenuItem<Wry>> = vec![&open, &top];
    items.extend(labels.iter().map(|item| item as &dyn IsMenuItem<Wry>));
    items.extend([&bottom as &dyn IsMenuItem<Wry>, &quit]);
    let menu = Menu::with_items(app, &items)?;

    if let Some(tray) = app.tray_by_id(TRAY) {
        return tray.set_menu(Some(menu));
    }

    TrayIconBuilder::with_id(TRAY)
        .icon(
            app.default_window_icon()
                .cloned()
                .expect("the bundle carries an icon"),
        )
        .tooltip("Lorekeeper")
        .menu(&menu)
        .show_menu_on_left_click(true)
        .on_menu_event(|app, event| match event.id.as_ref() {
            OPEN => show(app),
            QUIT => app.exit(0),
            _ => {}
        })
        .build(app)?;

    Ok(())
}

fn show(app: &AppHandle) {
    if let Some(window) = app.get_webview_window(WINDOW) {
        let _ = window.show();
        let _ = window.unminimize();
        let _ = window.set_focus();
    }
}

fn fail(app: &AppHandle, failure: &str) {
    let text = serde_json::to_string(failure).unwrap_or_default();
    if let Some(window) = app.get_webview_window(WINDOW) {
        let _ = window.eval(format!(
            "document.getElementById('status').textContent = 'The lorekeeper could not wake up.';\
             document.getElementById('failure').textContent = {text};"
        ));
    }
}

fn stop(app: &AppHandle) {
    let Some(mut server) = app.state::<Server>().0.lock().unwrap().take() else {
        return;
    };

    #[cfg(unix)]
    unsafe {
        libc::killpg(server.id() as libc::pid_t, libc::SIGTERM);
    }
    #[cfg(not(unix))]
    let _ = server.kill();

    let _ = server.wait();
}

fn secret() -> Result<String, Failure> {
    let mut bytes = [0u8; 32];
    getrandom::getrandom(&mut bytes).map_err(|failure| failure.to_string())?;

    Ok(bytes.iter().map(|byte| format!("{byte:02x}")).collect())
}

fn refresh_bootstrap_cache(cache: &Path, code: &Path, version: &str) -> std::io::Result<()> {
    let mut hasher = DefaultHasher::new();
    version.hash(&mut hasher);
    for file in ["composer.lock", "bootstrap/providers.php"] {
        fs::read(code.join(file))
            .unwrap_or_default()
            .hash(&mut hasher);
    }
    let stamp = format!("{:x}", hasher.finish());
    let file = cache.join(".stamp");

    if fs::read_to_string(&file).is_ok_and(|kept| kept == stamp) {
        return Ok(());
    }
    if cache.exists() {
        fs::remove_dir_all(cache)?;
    }
    fs::create_dir_all(cache)?;
    fs::write(file, stamp)
}

fn path(path: &Path) -> String {
    path.to_string_lossy().into_owned()
}

#[cfg(test)]
mod tests {
    use super::*;

    fn folder(name: &str) -> PathBuf {
        let folder = std::env::temp_dir().join(format!("lorekeeper-{name}-{}", std::process::id()));
        let _ = fs::remove_dir_all(&folder);
        fs::create_dir_all(folder.join("bootstrap")).unwrap();
        folder
    }

    #[test]
    fn only_a_page_of_the_app_counts_as_the_app() {
        let url = "http://127.0.0.1:8123";

        assert!(on_app(&Url::parse("http://127.0.0.1:8123/").unwrap(), url));
        assert!(on_app(
            &Url::parse("http://127.0.0.1:8123/routines/1").unwrap(),
            url
        ));
        assert!(!on_app(
            &Url::parse("tauri://localhost/index.html").unwrap(),
            url
        ));
        assert!(!on_app(&Url::parse("http://localhost:1430/").unwrap(), url));
        assert!(!on_app(
            &Url::parse("http://127.0.0.1:8123/").unwrap(),
            "http://127.0.0.1:812"
        ));
    }

    #[cfg(target_os = "linux")]
    #[test]
    fn the_server_does_not_outlive_the_shell() {
        let mut server = thread::spawn(|| {
            let mut command = Command::new("sleep");
            command.arg("30");
            dies_with_parent(&mut command);
            command.spawn().unwrap()
        })
        .join()
        .unwrap();

        let since = Instant::now();
        while server.try_wait().unwrap().is_none() {
            assert!(
                since.elapsed() < Duration::from_secs(5),
                "the server outlived the shell"
            );
            thread::sleep(Duration::from_millis(20));
        }
    }

    #[test]
    fn a_new_bundle_drops_the_caches_the_old_one_left() {
        let code = folder("code");
        let cache = folder("data").join("bootstrap/cache");
        fs::write(code.join("composer.lock"), "nativephp/desktop").unwrap();
        refresh_bootstrap_cache(&cache, &code, "0.0.1").unwrap();
        fs::write(cache.join("packages.php"), "NativeServiceProvider").unwrap();

        fs::write(code.join("composer.lock"), "tauri").unwrap();
        refresh_bootstrap_cache(&cache, &code, "0.0.1").unwrap();

        assert!(!cache.join("packages.php").exists());
    }

    #[test]
    fn the_same_bundle_keeps_its_caches() {
        let code = folder("same-code");
        let cache = folder("same-data").join("bootstrap/cache");
        fs::write(code.join("composer.lock"), "tauri").unwrap();
        refresh_bootstrap_cache(&cache, &code, "0.0.1").unwrap();
        fs::write(cache.join("packages.php"), "cached").unwrap();

        refresh_bootstrap_cache(&cache, &code, "0.0.1").unwrap();
        assert!(cache.join("packages.php").exists());

        refresh_bootstrap_cache(&cache, &code, "0.0.2").unwrap();
        assert!(!cache.join("packages.php").exists());
    }
}

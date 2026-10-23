use std::env;
use std::fs;
use std::io;
use std::os::unix::process::CommandExt;
use std::path::{Path, PathBuf};
use std::process::{Child, Command, Stdio};
use std::thread;
use std::time::SystemTime;

use signal_hook::consts::{SIGHUP, SIGINT, SIGTERM};
use signal_hook::iterator::Signals;
use tauri::{AppHandle, Url};

use super::Platform;
use crate::window;

pub struct Linux;

impl Platform for Linux {
    const BUNDLED_PHP: &'static str = "php/php";

    fn ini_scan_dir(folder: &Path) -> String {
        format!(":{}", folder.display())
    }

    fn isolate(server: &mut Command) {
        server.process_group(0);
        dies_with_parent(server);
    }

    fn stop(server: &mut Child) {
        unsafe {
            libc::killpg(server.id() as libc::pid_t, libc::SIGTERM);
        }
        let _ = server.wait();
    }

    fn reap(group: &Path) {
        let leader = fs::read_to_string(group)
            .ok()
            .and_then(|text| text.trim().parse::<u32>().ok());
        if let Some(leader) = leader.filter(|leader| php_in_group(*leader)) {
            unsafe {
                libc::killpg(leader as libc::pid_t, libc::SIGTERM);
            }
        }
        let _ = fs::remove_file(group);
    }

    fn on_signals(app: &AppHandle) -> io::Result<()> {
        let mut signals = Signals::new([SIGTERM, SIGINT, SIGHUP])?;
        let app = app.clone();
        thread::spawn(move || {
            if signals.forever().next().is_some() {
                app.exit(0);
            }
        });

        Ok(())
    }

    fn notify(icon: &Path, title: &str, message: &str) {
        let _ = notify_rust::Notification::new()
            .appname("Lorekeeper")
            .summary(title)
            .body(message)
            .image_path(&format!("file://{}", icon.display()))
            .show();
    }

    fn introduce(icon: &Path) {
        let Some(home) = data_home() else {
            return;
        };
        let system = env::var("XDG_DATA_DIRS").unwrap_or_else(|_| SYSTEM_DATA.to_string());
        let system = match env::var("APPDIR") {
            Ok(appdir) => without_the_appimage(&system, &appdir),
            Err(_) => system,
        };
        if installed(&system) {
            return;
        }

        let Some(exec) = executable() else {
            return;
        };

        place(
            &home,
            &fs::read(icon).unwrap_or_default(),
            &desktop_entry(&exec, cfg!(debug_assertions)),
        );
    }

    fn start_with_computer(enabled: bool) {
        let Some(entry) = config_home().map(|config| config.join("autostart").join(AUTOSTART))
        else {
            return;
        };
        if !enabled {
            let _ = fs::remove_file(entry);
            return;
        }

        if let Some(exec) = executable() {
            let _ = keep(&entry, autostart_entry(&exec).as_bytes());
        }
    }

    fn open_externally(url: &Url) {
        let mut command = Command::new("xdg-open");
        command
            .arg(url.as_str())
            .stdin(Stdio::null())
            .stdout(Stdio::null())
            .stderr(Stdio::null());
        if let Ok(appdir) = env::var("APPDIR") {
            outside_the_appimage(&mut command, &appdir);
        }

        if let Ok(mut opener) = command.spawn() {
            thread::spawn(move || opener.wait());
        }
    }
}

const NAME: &str = "lorekeeper";
const AUTOSTART: &str = if cfg!(debug_assertions) {
    "lorekeeper-dev.desktop"
} else {
    "lorekeeper.desktop"
};
const SYSTEM_DATA: &str = "/usr/local/share:/usr/share";
const INSTALLED_AS: [&str; 3] = [
    "lorekeeper.desktop",
    "Lorekeeper.desktop",
    "com.dayvsonspacca.lorekeeper.desktop.desktop",
];

fn data_home() -> Option<PathBuf> {
    env::var("XDG_DATA_HOME")
        .ok()
        .filter(|dir| !dir.is_empty())
        .map(PathBuf::from)
        .or_else(|| {
            env::var("HOME")
                .ok()
                .map(|home| Path::new(&home).join(".local/share"))
        })
}

fn config_home() -> Option<PathBuf> {
    env::var("XDG_CONFIG_HOME")
        .ok()
        .filter(|dir| !dir.is_empty())
        .map(PathBuf::from)
        .or_else(|| {
            env::var("HOME")
                .ok()
                .map(|home| Path::new(&home).join(".config"))
        })
}

fn executable() -> Option<PathBuf> {
    env::var("APPIMAGE")
        .map(PathBuf::from)
        .or_else(|_| env::current_exe())
        .ok()
}

fn installed(dirs: &str) -> bool {
    dirs.split(':').filter(|dir| !dir.is_empty()).any(|dir| {
        INSTALLED_AS
            .iter()
            .any(|name| Path::new(dir).join("applications").join(name).is_file())
    })
}

fn desktop_entry(exec: &Path, hidden: bool) -> String {
    let mut entry = launcher(exec, "");
    if hidden {
        entry.push_str("NoDisplay=true\n");
    }

    entry
}

fn autostart_entry(exec: &Path) -> String {
    let mut entry = launcher(exec, &format!(" {}", window::HIDDEN));
    entry.push_str("X-GNOME-Autostart-enabled=true\n");

    entry
}

fn launcher(exec: &Path, arguments: &str) -> String {
    let exec = exec
        .display()
        .to_string()
        .replace('\\', "\\\\")
        .replace('"', "\\\"")
        .replace('`', "\\`")
        .replace('$', "\\$")
        .replace('%', "%%");
    format!(
        "[Desktop Entry]\nType=Application\nName=Lorekeeper\n\
         Comment=Keep several AdventureQuest Worlds bots playing on your machine\n\
         Exec=\"{exec}\"{arguments}\nIcon={NAME}\nStartupWMClass={NAME}\nCategories=Game;\nTerminal=false\n"
    )
}

fn place(home: &Path, icon: &[u8], entry: &str) {
    let theme = home.join("icons/hicolor");
    let icon = keep(
        &theme.join("128x128/apps").join(format!("{NAME}.png")),
        icon,
    );
    let entry = keep(
        &home.join("applications").join(format!("{NAME}.desktop")),
        entry.as_bytes(),
    );

    if matches!(icon, Ok(true)) || matches!(entry, Ok(true)) {
        let _ = fs::File::open(&theme).and_then(|folder| folder.set_modified(SystemTime::now()));
    }
}

fn keep(path: &Path, contents: &[u8]) -> io::Result<bool> {
    if contents.is_empty() || fs::read(path).is_ok_and(|kept| kept == contents) {
        return Ok(false);
    }
    if let Some(folder) = path.parent() {
        fs::create_dir_all(folder)?;
    }

    fs::write(path, contents)?;

    Ok(true)
}

const APPIMAGE_VARIABLES: [&str; 15] = [
    "APPDIR",
    "APPIMAGE",
    "ARGV0",
    "OWD",
    "LD_LIBRARY_PATH",
    "GTK_DATA_PREFIX",
    "GTK_THEME",
    "GTK_EXE_PREFIX",
    "GTK_PATH",
    "GTK_IM_MODULE_FILE",
    "GSETTINGS_SCHEMA_DIR",
    "GI_TYPELIB_PATH",
    "GIO_MODULE_DIR",
    "GDK_PIXBUF_MODULEDIR",
    "GDK_PIXBUF_MODULE_FILE",
];

fn outside_the_appimage(command: &mut Command, appdir: &str) {
    for variable in APPIMAGE_VARIABLES {
        command.env_remove(variable);
    }
    if let Ok(dirs) = env::var("XDG_DATA_DIRS") {
        command.env("XDG_DATA_DIRS", without_the_appimage(&dirs, appdir));
    }
}

fn without_the_appimage(dirs: &str, appdir: &str) -> String {
    dirs.split(':')
        .filter(|dir| !dir.is_empty() && !dir.starts_with(appdir))
        .collect::<Vec<_>>()
        .join(":")
}

fn dies_with_parent(command: &mut Command) {
    unsafe {
        command.pre_exec(|| {
            libc::prctl(libc::PR_SET_PDEATHSIG, libc::SIGTERM);
            Ok(())
        });
    }
}

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

#[cfg(test)]
mod tests {
    use std::time::{Duration, Instant};

    use super::*;

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
    fn the_browser_does_not_see_the_appimage_data() {
        assert_eq!(
            without_the_appimage(
                "/tmp/.mount_Lore/usr/share:/usr/share:/usr/local/share:",
                "/tmp/.mount_Lore"
            ),
            "/usr/share:/usr/local/share"
        );
    }

    #[test]
    fn the_desktop_entry_names_the_icon_and_window_and_quotes_the_path() {
        let entry = desktop_entry(
            Path::new("/home/hero/Apps/Lore keeper \"1\".AppImage"),
            false,
        );

        assert!(entry.starts_with("[Desktop Entry]\nType=Application\nName=Lorekeeper\n"));
        assert!(entry.contains("\nExec=\"/home/hero/Apps/Lore keeper \\\"1\\\".AppImage\"\n"));
        assert!(entry.contains("\nIcon=lorekeeper\nStartupWMClass=lorekeeper\n"));
        assert!(!entry.contains("NoDisplay"));
        assert!(desktop_entry(Path::new("/dev/lorekeeper"), true).ends_with("NoDisplay=true\n"));
    }

    #[test]
    fn the_computer_starts_the_app_hidden_in_the_tray() {
        let entry = autostart_entry(Path::new("/home/hero/Apps/Lorekeeper.AppImage"));

        assert!(entry.contains("\nExec=\"/home/hero/Apps/Lorekeeper.AppImage\" --hidden\n"));
        assert!(entry.ends_with("X-GNOME-Autostart-enabled=true\n"));
        assert!(!entry.contains("NoDisplay"));
    }

    #[test]
    fn an_installed_package_keeps_its_own_entry() {
        let root = env::temp_dir().join(format!("lorekeeper-installed-{}", std::process::id()));
        let empty = root.join("empty");
        let system = root.join("system");
        fs::create_dir_all(empty.join("applications")).unwrap();
        fs::create_dir_all(system.join("applications")).unwrap();
        fs::write(system.join("applications/Lorekeeper.desktop"), "").unwrap();

        assert!(!installed(&format!("{}:", empty.display())));
        assert!(installed(&format!(
            "{}:{}",
            empty.display(),
            system.display()
        )));
        fs::remove_dir_all(root).unwrap();
    }

    #[test]
    fn a_new_icon_tells_the_desktop_to_look_for_icons_again() {
        let home = env::temp_dir().join(format!("lorekeeper-icon-{}", std::process::id()));
        let theme = home.join("icons/hicolor");
        let long_ago = SystemTime::now() - Duration::from_secs(3600);
        let aged = || {
            fs::create_dir_all(theme.join("128x128/apps")).unwrap();
            fs::File::open(&theme)
                .unwrap()
                .set_modified(long_ago)
                .unwrap();
        };
        let looked = || fs::metadata(&theme).unwrap().modified().unwrap();

        aged();
        place(&home, b"icon", "entry");
        assert!(looked() > long_ago);
        assert!(theme.join("128x128/apps/lorekeeper.png").is_file());

        aged();
        place(&home, b"icon", "entry");
        assert_eq!(looked(), long_ago);

        place(&home, b"icon", "moved entry");
        assert!(looked() > long_ago);
        fs::remove_dir_all(home).unwrap();
    }

    #[test]
    fn a_file_is_written_only_when_it_changes() {
        let root = env::temp_dir().join(format!("lorekeeper-keep-{}", std::process::id()));
        let path = root.join("icons/lorekeeper.png");

        assert!(keep(&path, b"one").unwrap());
        let written = fs::metadata(&path).unwrap().modified().unwrap();
        thread::sleep(std::time::Duration::from_millis(20));
        assert!(!keep(&path, b"one").unwrap());
        assert_eq!(fs::metadata(&path).unwrap().modified().unwrap(), written);
        assert!(keep(&path, b"two").unwrap());
        assert_eq!(fs::read(&path).unwrap(), b"two");
        assert!(!keep(&path, b"").unwrap());
        assert_eq!(fs::read(&path).unwrap(), b"two");
        fs::remove_dir_all(root).unwrap();
    }

    #[test]
    fn php_reads_its_extra_settings_after_its_own() {
        assert_eq!(Linux::ini_scan_dir(Path::new("/data/php")), ":/data/php");
    }
}

mod data;

use std::fs::{self, File};
use std::path::PathBuf;
use std::process::{Child, Command, Stdio};

use tauri::{AppHandle, Manager};

use crate::platform::{Current, Platform};
use crate::Failure;
use data::Data;

const ROUTER: &str = "vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php";
const WORKERS: &str = "2";

pub struct Php {
    binary: PathBuf,
    code: PathBuf,
    data: Data,
    env: Vec<(&'static str, String)>,
}

impl Php {
    pub fn locate(app: &AppHandle, url: &str, secret: &str) -> Result<Self, Failure> {
        let (binary, code, root) = if cfg!(debug_assertions) {
            (
                PathBuf::from("php"),
                PathBuf::from(env!("CARGO_MANIFEST_DIR")).join(".."),
                app.path().app_data_dir()?.join("dev"),
            )
        } else {
            let resources = app.path().resource_dir()?;
            (
                resources.join(Current::BUNDLED_PHP),
                resources.join("app"),
                app.path().app_data_dir()?,
            )
        };
        let binary = overridden("LOREKEEPER_PHP").unwrap_or(binary);
        let code = fs::canonicalize(overridden("LOREKEEPER_APP").unwrap_or(code))?;
        let data = Data::prepare(root, &code, &app.package_info().version.to_string())?;

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
            ("LOREKEEPER_DATA_PATH", data.root().display().to_string()),
            ("LARAVEL_STORAGE_PATH", data.storage().display().to_string()),
            ("DB_CONNECTION", "sqlite".to_string()),
            ("DB_DATABASE", data.database().display().to_string()),
            ("PHP_INI_SCAN_DIR", Current::ini_scan_dir(&data.ini())),
            ("PHP_CLI_SERVER_WORKERS", WORKERS.to_string()),
        ];

        Ok(Self {
            binary,
            code,
            data,
            env,
        })
    }

    pub fn icon(&self) -> PathBuf {
        self.data.icon()
    }

    pub fn reap(&self) {
        Current::reap(&self.data.group());
    }

    pub fn boot(&self) -> Result<(), Failure> {
        let boot = self.command(&["artisan", "desktop:boot"]).output()?;
        if boot.status.success() {
            return Ok(());
        }

        Err(format!(
            "desktop:boot failed\n{}{}",
            String::from_utf8_lossy(&boot.stdout),
            String::from_utf8_lossy(&boot.stderr)
        )
        .into())
    }

    pub fn serve(&self, port: u16) -> Result<Child, Failure> {
        let router = self.code.join(ROUTER).display().to_string();
        let mut command = self.command(&["-S", &format!("127.0.0.1:{port}"), &router]);
        command
            .current_dir(self.code.join("public"))
            .stdout(Stdio::null())
            .stderr(File::create(self.data.server_log())?);
        Current::isolate(&mut command);

        let server = command.spawn()?;
        fs::write(self.data.group(), server.id().to_string())?;

        Ok(server)
    }

    fn command(&self, args: &[&str]) -> Command {
        let mut command = Command::new(&self.binary);
        command
            .args(args)
            .current_dir(&self.code)
            .envs(self.env.iter().map(|(key, value)| (key, value)));
        command
    }
}

fn overridden(variable: &str) -> Option<PathBuf> {
    std::env::var_os(variable).map(PathBuf::from)
}

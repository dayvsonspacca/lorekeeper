use std::io;
use std::path::Path;
use std::process::{Child, Command};

use tauri::{AppHandle, Url};

pub trait Platform {
    const BUNDLED_PHP: &'static str;

    fn ini_scan_dir(folder: &Path) -> String;

    fn isolate(server: &mut Command);

    fn stop(server: &mut Child);

    fn reap(group: &Path);

    fn on_signals(app: &AppHandle) -> io::Result<()>;

    fn open_externally(url: &Url);
}

#[cfg(target_os = "linux")]
mod linux;
#[cfg(target_os = "linux")]
pub type Current = linux::Linux;

#[cfg(not(target_os = "linux"))]
compile_error!(
    "the desktop shell only runs on Linux so far: implement Platform in src/platform/<os>.rs"
);

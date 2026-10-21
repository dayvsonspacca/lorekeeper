use std::fs;
use std::io;
use std::os::unix::process::CommandExt;
use std::path::Path;
use std::process::{Child, Command};
use std::thread;

use signal_hook::consts::{SIGHUP, SIGINT, SIGTERM};
use signal_hook::iterator::Signals;
use tauri::AppHandle;

use super::Platform;

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
    fn php_reads_its_extra_settings_after_its_own() {
        assert_eq!(Linux::ini_scan_dir(Path::new("/data/php")), ":/data/php");
    }
}

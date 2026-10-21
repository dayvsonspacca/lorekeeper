use std::net::TcpListener;
use std::process::Child;
use std::sync::Mutex;
use std::thread;
use std::time::{Duration, Instant};

use tauri::{AppHandle, Manager};

use crate::php::Php;
use crate::platform::{Current, Platform};
use crate::timeline::Timeline;
use crate::{poll, window, Failure};

const PATIENCE: Duration = Duration::from_secs(30);

#[derive(Default)]
pub struct Server(Mutex<Option<Child>>);

pub fn start(app: AppHandle, timeline: Timeline) {
    thread::spawn(move || {
        if let Err(failure) = run(&app, timeline) {
            window::fail(&app, &failure.to_string());
        }
    });
}

pub fn stop(app: &AppHandle) {
    if let Some(mut server) = app.state::<Server>().0.lock().unwrap().take() {
        Current::stop(&mut server);
    }
}

fn run(app: &AppHandle, mut timeline: Timeline) -> Result<(), Failure> {
    timeline.mark("thread");
    let port = TcpListener::bind("127.0.0.1:0")?.local_addr()?.port();
    let secret = secret()?;
    let url = format!("http://127.0.0.1:{port}");

    let php = Php::locate(app, &url, &secret)?;
    timeline.mark("data");
    php.reap();
    timeline.mark("reap");

    let server = php.serve(port)?;
    app.state::<Server>().0.lock().unwrap().replace(server);
    let warming = {
        let url = url.clone();
        thread::spawn(move || wait_until_up(&url))
    };

    window::status(app, "Bringing the database up to date…");
    php.boot()?;
    timeline.mark("boot");

    window::status(app, "Opening the app…");
    warming
        .join()
        .map_err(|_| "the server check stopped unexpectedly")??;
    timeline.mark("server");
    window::open(app, &url, &secret)?;
    timeline.mark("open");
    php.note_startup(&timeline.summary());

    poll::watch(app, &url, &secret, &php.icon());

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

fn secret() -> Result<String, Failure> {
    let mut bytes = [0u8; 32];
    getrandom::getrandom(&mut bytes).map_err(|failure| failure.to_string())?;

    Ok(bytes.iter().map(|byte| format!("{byte:02x}")).collect())
}

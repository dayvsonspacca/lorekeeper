use std::path::Path;
use std::thread;
use std::time::Duration;

use serde::Deserialize;
use tauri::AppHandle;

use crate::platform::{Current, Platform};
use crate::{tray, window, Failure};

const EVERY: Duration = Duration::from_secs(3);
const SECRET_HEADER: &str = "X-Lorekeeper-Secret";

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

pub fn watch(app: &AppHandle, url: &str, secret: &str, icon: &Path) {
    let mut shown: Vec<String> = Vec::new();
    loop {
        if let Err(failure) = window::open(app, url) {
            eprintln!("lorekeeper: could not open the app: {failure}");
        }

        if let Ok(poll) = poll(url, secret) {
            if poll.bots != shown {
                let handle = app.clone();
                let bots = poll.bots.clone();
                let _ = app.run_on_main_thread(move || {
                    let _ = tray::update(&handle, &bots);
                });
                shown = poll.bots;
            }

            for notice in poll.notices {
                Current::notify(icon, &notice.title, &notice.message);
            }

            if poll.reload {
                window::reload(app);
            }
        }

        thread::sleep(EVERY);
    }
}

fn poll(url: &str, secret: &str) -> Result<Poll, Failure> {
    Ok(ureq::get(&format!("{url}/shell"))
        .set(SECRET_HEADER, secret)
        .call()?
        .into_json::<Poll>()?)
}

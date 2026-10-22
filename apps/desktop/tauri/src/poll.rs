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
    playing: bool,
    start_with_computer: bool,
    notices: Vec<Notice>,
    reload: bool,
}

#[derive(Deserialize)]
struct Notice {
    title: String,
    message: String,
}

pub fn watch(app: &AppHandle, url: &str, secret: &str, icon: &Path) {
    let mut shown: (Vec<String>, bool) = (Vec::new(), false);
    let mut starting: Option<bool> = None;
    loop {
        if let Err(failure) = window::open(app, url, secret) {
            eprintln!("lorekeeper: could not open the app: {failure}");
        }

        if let Ok(poll) = poll(url, secret) {
            if starting != Some(poll.start_with_computer) {
                Current::start_with_computer(poll.start_with_computer);
                starting = Some(poll.start_with_computer);
            }

            let menu = (poll.bots, poll.playing);
            if menu != shown {
                let handle = app.clone();
                let (bots, playing) = menu.clone();
                let _ = app.run_on_main_thread(move || {
                    let _ = tray::update(&handle, &bots, playing);
                });
                shown = menu;
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

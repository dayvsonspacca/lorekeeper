use std::path::PathBuf;
use std::sync::Mutex;

use serde::Serialize;
use tauri::{AppHandle, Manager, State, Url};

use crate::platform::{Current, Platform};
use crate::timeline::Timeline;
use crate::{shell, window};

pub struct Splash(Mutex<Progress>);

struct Progress {
    step: &'static str,
    failure: Option<String>,
    logs: Option<PathBuf>,
}

#[derive(Serialize)]
pub struct View {
    version: String,
    step: &'static str,
    failure: Option<String>,
    logs: bool,
}

impl Default for Splash {
    fn default() -> Self {
        Self(Mutex::new(Progress::default()))
    }
}

impl Default for Progress {
    fn default() -> Self {
        Self {
            step: "data",
            failure: None,
            logs: None,
        }
    }
}

pub fn step(app: &AppHandle, step: &'static str) {
    update(app, |progress| progress.step = step);
}

pub fn fail(app: &AppHandle, failure: &str) {
    update(app, |progress| progress.failure = Some(failure.to_string()));
}

pub fn logs_at(app: &AppHandle, folder: PathBuf) {
    update(app, |progress| progress.logs = Some(folder));
}

#[tauri::command]
pub fn splash(app: AppHandle, splash: State<Splash>) -> View {
    view(&app, &splash.0.lock().unwrap())
}

#[tauri::command]
pub fn retry(app: AppHandle, splash: State<Splash>) {
    if splash.0.lock().unwrap().failure.is_none() {
        return;
    }

    shell::stop(&app);
    update(&app, |progress| *progress = Progress::default());
    shell::start(app, Timeline::start());
}

#[tauri::command]
pub fn open_logs(splash: State<Splash>) {
    let logs = splash.0.lock().unwrap().logs.clone();
    if let Some(url) = logs.and_then(|folder| Url::from_directory_path(folder).ok()) {
        Current::open_externally(&url);
    }
}

fn update(app: &AppHandle, change: impl FnOnce(&mut Progress)) {
    let splash = app.state::<Splash>();
    let view = {
        let mut progress = splash.0.lock().unwrap();
        change(&mut progress);
        view(app, &progress)
    };

    if let Ok(view) = serde_json::to_string(&view) {
        window::eval(app, &format!("window.lorekeeper?.render({view})"));
    }
}

fn view(app: &AppHandle, progress: &Progress) -> View {
    View {
        version: app.package_info().version.to_string(),
        step: progress.step,
        failure: progress.failure.clone(),
        logs: progress.logs.is_some(),
    }
}

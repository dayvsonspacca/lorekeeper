use tauri::webview::NewWindowResponse;
use tauri::{App, AppHandle, Manager, Url, WebviewUrl, WebviewWindowBuilder, Window, WindowEvent};

use crate::platform::{Current, Platform};
use crate::Failure;

const MAIN: &str = "main";
const SECRET_QUERY: &str = "lorekeeper_secret";

pub fn create(app: &App) -> tauri::Result<()> {
    WebviewWindowBuilder::new(app, MAIN, WebviewUrl::App("index.html".into()))
        .title("Lorekeeper")
        .inner_size(1280.0, 820.0)
        .min_inner_size(900.0, 600.0)
        .on_navigation(|target| {
            if leaves_the_app(target) {
                Current::open_in_browser(target);
                return false;
            }

            true
        })
        .on_new_window(|target, _| {
            if leaves_the_app(&target) {
                Current::open_in_browser(&target);
            }

            NewWindowResponse::Deny
        })
        .build()?;

    Ok(())
}

pub fn keep_in_tray(window: &Window, event: &WindowEvent) {
    if let WindowEvent::CloseRequested { api, .. } = event {
        let _ = window.hide();
        api.prevent_close();
    }
}

pub fn show(app: &AppHandle) {
    if let Some(window) = app.get_webview_window(MAIN) {
        let _ = window.show();
        let _ = window.unminimize();
        let _ = window.set_focus();
    }
}

pub fn open(app: &AppHandle, url: &str, secret: &str) -> Result<(), Failure> {
    let Some(window) = app.get_webview_window(MAIN) else {
        return Ok(());
    };
    let current = window.url()?;
    if on_app(&current, url) {
        return Ok(());
    }

    eprintln!("lorekeeper: the window is on {current}, opening {url}");
    window.navigate(entry(url, secret)?)?;

    Ok(())
}

pub fn reload(app: &AppHandle) {
    if let Some(window) = app.get_webview_window(MAIN) {
        let _ = window.eval("location.reload()");
    }
}

pub fn fail(app: &AppHandle, failure: &str) {
    let text = serde_json::to_string(failure).unwrap_or_default();
    if let Some(window) = app.get_webview_window(MAIN) {
        let _ = window.eval(format!(
            "document.getElementById('status').textContent = 'The lorekeeper could not wake up.';\
             document.getElementById('failure').textContent = {text};"
        ));
    }
}

fn entry(url: &str, secret: &str) -> Result<Url, Failure> {
    let mut entry = Url::parse(&format!("{url}/"))?;
    entry.query_pairs_mut().append_pair(SECRET_QUERY, secret);

    Ok(entry)
}

fn leaves_the_app(target: &Url) -> bool {
    matches!(target.scheme(), "http" | "https")
        && !matches!(
            target.host_str(),
            Some("127.0.0.1" | "localhost" | "[::1]" | "tauri.localhost")
        )
}

fn on_app(current: &Url, url: &str) -> bool {
    current.as_str().starts_with(&format!("{url}/"))
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn the_window_enters_the_app_with_the_secret() {
        assert_eq!(
            entry("http://127.0.0.1:8123", "c0ffee").unwrap().as_str(),
            "http://127.0.0.1:8123/?lorekeeper_secret=c0ffee"
        );
    }

    #[test]
    fn only_pages_off_the_machine_open_in_the_browser() {
        for outside in [
            "https://github.com/dayvsonspacca/lorekeeper",
            "http://aqwwiki.wikidot.com/",
        ] {
            assert!(leaves_the_app(&Url::parse(outside).unwrap()), "{outside}");
        }
        for inside in [
            "http://127.0.0.1:8123/routines",
            "http://localhost:1430/",
            "tauri://localhost/index.html",
            "about:blank",
        ] {
            assert!(!leaves_the_app(&Url::parse(inside).unwrap()), "{inside}");
        }
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
}

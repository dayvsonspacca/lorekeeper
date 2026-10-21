use tauri::menu::{IsMenuItem, Menu, MenuItem, PredefinedMenuItem};
use tauri::tray::TrayIconBuilder;
use tauri::{AppHandle, Wry};

use crate::window;

const TRAY: &str = "main";
const OPEN: &str = "open";
const QUIT: &str = "quit";

pub fn show(app: &AppHandle) {
    let handle = app.clone();
    let _ = app.run_on_main_thread(move || {
        let _ = update(&handle, &[]);
    });
}

pub fn exists(app: &AppHandle) -> bool {
    app.tray_by_id(TRAY).is_some()
}

pub fn update(app: &AppHandle, bots: &[String]) -> tauri::Result<()> {
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
            OPEN => window::show(app),
            QUIT => app.exit(0),
            _ => {}
        })
        .build(app)?;

    Ok(())
}

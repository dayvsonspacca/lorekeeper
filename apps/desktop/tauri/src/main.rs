mod php;
mod platform;
mod poll;
mod shell;
mod tray;
mod window;

use tauri::RunEvent;

use platform::{Current, Platform};

type Failure = Box<dyn std::error::Error + Send + Sync>;

fn main() {
    tauri::Builder::default()
        .plugin(tauri_plugin_single_instance::init(|app, _, _| {
            window::show(app)
        }))
        .plugin(tauri_plugin_window_state::Builder::default().build())
        .manage(shell::Server::default())
        .setup(|app| {
            window::create(app)?;
            tray::update(app.handle(), &[])?;
            Current::on_signals(app.handle())?;
            shell::start(app.handle().clone());

            Ok(())
        })
        .on_window_event(window::keep_in_tray)
        .build(tauri::generate_context!())
        .expect("the shell could not be built")
        .run(|app, event| {
            if let RunEvent::Exit = event {
                shell::stop(app);
            }
        });
}

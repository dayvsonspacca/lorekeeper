mod php;
mod platform;
mod poll;
mod shell;
mod timeline;
mod tray;
mod window;

use tauri::RunEvent;

use platform::{Current, Platform};
use timeline::Timeline;

type Failure = Box<dyn std::error::Error + Send + Sync>;

fn main() {
    let mut timeline = Timeline::start();

    tauri::Builder::default()
        .plugin(tauri_plugin_single_instance::init(|app, _, _| {
            window::show(app)
        }))
        .plugin(tauri_plugin_window_state::Builder::default().build())
        .manage(shell::Server::default())
        .setup(move |app| {
            timeline.mark("shell");
            window::create(app)?;
            timeline.mark("window");
            tray::update(app.handle(), &[])?;
            timeline.mark("tray");
            Current::on_signals(app.handle())?;
            shell::start(app.handle().clone(), timeline);

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

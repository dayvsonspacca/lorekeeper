mod php;
mod platform;
mod poll;
mod shell;
mod splash;
mod timeline;
mod tray;
mod window;

use tauri::RunEvent;
use tauri_plugin_window_state::StateFlags;

use platform::{Current, Platform};
use timeline::Timeline;

type Failure = Box<dyn std::error::Error + Send + Sync>;

fn main() {
    let mut timeline = Timeline::start();

    tauri::Builder::default()
        .plugin(tauri_plugin_single_instance::init(|app, _, _| {
            window::show(app)
        }))
        .plugin(
            tauri_plugin_window_state::Builder::default()
                .with_state_flags(StateFlags::all() - StateFlags::VISIBLE)
                .build(),
        )
        .manage(shell::Server::default())
        .manage(splash::Splash::default())
        .invoke_handler(tauri::generate_handler![
            splash::splash,
            splash::retry,
            splash::open_logs
        ])
        .setup(move |app| {
            timeline.mark("shell");
            window::create(app)?;
            timeline.mark("window");
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

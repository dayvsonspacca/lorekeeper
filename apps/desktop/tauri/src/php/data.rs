use std::fs::{self, OpenOptions};
use std::hash::{DefaultHasher, Hash, Hasher};
use std::io;
use std::path::{Path, PathBuf};

const ICON: &[u8] = include_bytes!("../../icons/128x128.png");
const INI: &str = "memory_limit = 512M\nvariables_order = \"EGPCS\"\nopcache.enable = 1\n";
const STORAGE: [&str; 5] = [
    "app",
    "framework/cache/data",
    "framework/sessions",
    "framework/views",
    "logs",
];

pub struct Data {
    root: PathBuf,
}

impl Data {
    pub fn prepare(root: PathBuf, code: &Path, version: &str) -> io::Result<Self> {
        let data = Self { root };
        for folder in STORAGE {
            fs::create_dir_all(data.storage().join(folder))?;
        }
        refresh_bootstrap_cache(&data.root.join("bootstrap/cache"), code, version)?;
        OpenOptions::new()
            .create(true)
            .append(true)
            .open(data.database())?;
        fs::create_dir_all(data.ini())?;
        fs::write(data.ini().join("lorekeeper.ini"), INI)?;
        fs::write(data.icon(), ICON)?;

        Ok(data)
    }

    pub fn root(&self) -> &Path {
        &self.root
    }

    pub fn storage(&self) -> PathBuf {
        self.root.join("storage")
    }

    pub fn database(&self) -> PathBuf {
        self.root.join("database.sqlite")
    }

    pub fn ini(&self) -> PathBuf {
        self.root.join("php")
    }

    pub fn icon(&self) -> PathBuf {
        self.root.join("icon.png")
    }

    pub fn startup_log(&self) -> PathBuf {
        self.storage().join("logs/startup.log")
    }

    pub fn server_log(&self) -> PathBuf {
        self.storage().join("logs/server.log")
    }

    pub fn group(&self) -> PathBuf {
        self.root.join("server.pgid")
    }
}

fn refresh_bootstrap_cache(cache: &Path, code: &Path, version: &str) -> io::Result<()> {
    let mut hasher = DefaultHasher::new();
    version.hash(&mut hasher);
    for file in ["composer.lock", "bootstrap/providers.php"] {
        fs::read(code.join(file))
            .unwrap_or_default()
            .hash(&mut hasher);
    }
    let stamp = format!("{:x}", hasher.finish());
    let file = cache.join(".stamp");

    if fs::read_to_string(&file).is_ok_and(|kept| kept == stamp) {
        return Ok(());
    }
    if cache.exists() {
        fs::remove_dir_all(cache)?;
    }
    fs::create_dir_all(cache)?;
    fs::write(file, stamp)
}

#[cfg(test)]
mod tests {
    use super::*;

    fn folder(name: &str) -> PathBuf {
        let folder = std::env::temp_dir().join(format!("lorekeeper-{name}-{}", std::process::id()));
        let _ = fs::remove_dir_all(&folder);
        fs::create_dir_all(folder.join("bootstrap")).unwrap();
        folder
    }

    #[test]
    fn a_new_bundle_drops_the_caches_the_old_one_left() {
        let code = folder("code");
        let cache = folder("data").join("bootstrap/cache");
        fs::write(code.join("composer.lock"), "nativephp/desktop").unwrap();
        refresh_bootstrap_cache(&cache, &code, "0.0.1").unwrap();
        fs::write(cache.join("packages.php"), "NativeServiceProvider").unwrap();

        fs::write(code.join("composer.lock"), "tauri").unwrap();
        refresh_bootstrap_cache(&cache, &code, "0.0.1").unwrap();

        assert!(!cache.join("packages.php").exists());
    }

    #[test]
    fn the_same_bundle_keeps_its_caches() {
        let code = folder("same-code");
        let cache = folder("same-data").join("bootstrap/cache");
        fs::write(code.join("composer.lock"), "tauri").unwrap();
        refresh_bootstrap_cache(&cache, &code, "0.0.1").unwrap();
        fs::write(cache.join("packages.php"), "cached").unwrap();

        refresh_bootstrap_cache(&cache, &code, "0.0.1").unwrap();
        assert!(cache.join("packages.php").exists());

        refresh_bootstrap_cache(&cache, &code, "0.0.2").unwrap();
        assert!(!cache.join("packages.php").exists());
    }

    #[test]
    fn a_data_folder_is_ready_for_laravel() {
        let code = folder("ready-code");
        let data = Data::prepare(folder("ready-data"), &code, "0.0.1").unwrap();

        for folder in STORAGE {
            assert!(data.storage().join(folder).is_dir(), "{folder} is missing");
        }
        assert!(data.database().is_file());
        assert!(data.ini().join("lorekeeper.ini").is_file());
        assert_eq!(fs::read(data.icon()).unwrap(), ICON);
    }
}

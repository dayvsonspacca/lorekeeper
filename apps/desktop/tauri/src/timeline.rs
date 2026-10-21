use std::time::{Duration, Instant};

pub struct Timeline {
    since: Instant,
    last: Duration,
    phases: Vec<(&'static str, Duration)>,
}

impl Timeline {
    pub fn start() -> Self {
        Self {
            since: Instant::now(),
            last: Duration::ZERO,
            phases: Vec::new(),
        }
    }

    pub fn mark(&mut self, phase: &'static str) {
        self.record(phase, self.since.elapsed());
    }

    pub fn summary(&self) -> String {
        let phases = self
            .phases
            .iter()
            .map(|(phase, took)| format!("{phase} {}", took.as_millis()))
            .collect::<Vec<_>>()
            .join(", ");

        format!("started in {} ms ({phases})", self.last.as_millis())
    }

    fn record(&mut self, phase: &'static str, at: Duration) {
        self.phases.push((phase, at.saturating_sub(self.last)));
        self.last = at;
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn the_summary_tells_how_long_each_phase_took() {
        let mut timeline = Timeline::start();

        timeline.record("window", Duration::from_millis(120));
        timeline.record("boot", Duration::from_millis(420));
        timeline.record("server", Duration::from_millis(450));

        assert_eq!(
            timeline.summary(),
            "started in 450 ms (window 120, boot 300, server 30)"
        );
    }
}

<?php
require_once 'config.php';
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// When a pomodoro finishes, save to DB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duration'])) {
    $duration = intval($_POST['duration']); // minutes
    if ($duration > 0) {
        $stmt = $pdo->prepare("INSERT INTO study_sessions (user_id, duration, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user_id, $duration]);
        echo 'saved';
    } else {
        echo 'invalid';
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pomodoro Timer - StudyBuddy</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/dashboard-style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">StudyBuddy</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <a class="nav-link" href="subjects.php"><i class="fas fa-book"></i> Subjects</a>
                <a class="nav-link active" href="timer.php"><i class="fas fa-clock"></i> Timer</a>
                <a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

<div class="container mt-4">
    <div class="page-header fade-in text-center">
        <h2>🍅 Pomodoro Study Timer</h2>
        <p>Stay focused and productive with the Pomodoro Technique</p>
    </div>

    <div class="timer-info fade-in text-center">
        <div class="row">
            <div class="col-md-4">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="fas fa-brain text-primary" style="font-size: 2rem;"></i>
                    <div>
                        <strong>Focus Time</strong>
                        <p class="mb-0 text-muted">25 minutes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="fas fa-coffee text-success" style="font-size: 2rem;"></i>
                    <div>
                        <strong>Short Break</strong>
                        <p class="mb-0 text-muted">5 minutes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="fas fa-spa text-info" style="font-size: 2rem;"></i>
                    <div>
                        <strong>Long Break</strong>
                        <p class="mb-0 text-muted">15 minutes</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="timer-container fade-in">
        <div id="session-type" class="session-type text-primary fw-bold text-center">
            <i class="fas fa-brain"></i> Focus Time
        </div>
        <div id="timer-display">25:00</div>

        <div class="d-flex justify-content-center flex-wrap gap-2">
            <button id="startBtn" class="btn btn-success btn-timer">
                <i class="fas fa-play"></i> Start
            </button>
            <button id="pauseBtn" class="btn btn-warning btn-timer" disabled>
                <i class="fas fa-pause"></i> Pause
            </button>
            <button id="resetBtn" class="btn btn-danger btn-timer" disabled>
                <i class="fas fa-redo"></i> Reset
            </button>
        </div>

        <div class="mt-4 text-center">
            <div class="badge bg-secondary" style="font-size: 1rem; padding: 0.75rem 1.5rem;">
                <i class="fas fa-sync-alt"></i> Cycle: <span id="cycle-count">0</span> / 4
            </div>
        </div>
    </div>

    <div class="card fade-in mt-4">
        <div class="card-body">
            <h5 class="mb-3"><i class="fas fa-lightbulb text-warning"></i> How to use the Pomodoro Technique</h5>
            <ol class="mb-0">
                <li class="mb-2">Choose a task you want to work on</li>
                <li class="mb-2">Start the timer and work for 25 minutes with full focus</li>
                <li class="mb-2">Take a 5-minute short break when the timer rings</li>
                <li class="mb-2">After 4 focus sessions, take a 15-minute long break</li>
                <li>Repeat the process to maintain productivity</li>
            </ol>
        </div>
    </div>
</div>

<script>
const display = document.getElementById('timer-display');
const sessionTypeLabel = document.getElementById('session-type');
const cycleCountLabel = document.getElementById('cycle-count');
const startBtn = document.getElementById('startBtn');
const pauseBtn = document.getElementById('pauseBtn');
const resetBtn = document.getElementById('resetBtn');

// Pomodoro durations (seconds)
const FOCUS_TIME = 25 * 60;
const SHORT_BREAK = 5 * 60;
const LONG_BREAK = 15 * 60;

let timerInterval = null;
let isFocus = true;
let cycleCount = parseInt(localStorage.getItem('pomodoroCycles')) || 0;
let isRunning = false;

// Restore state or initialize
function getState() {
  return JSON.parse(localStorage.getItem('pomodoroState') || 'null');
}

function saveState(state) {
  localStorage.setItem('pomodoroState', JSON.stringify(state));
  localStorage.setItem('pomodoroCycles', cycleCount);
}

function updateDisplay(timeLeft) {
  const mins = String(Math.floor(timeLeft / 60)).padStart(2, '0');
  const secs = String(timeLeft % 60).padStart(2, '0');
  display.textContent = `${mins}:${secs}`;
  
  // Update page title
  document.title = `${mins}:${secs} - ${isFocus ? 'Focus' : 'Break'} - StudyBuddy`;
}

function updateSessionType() {
  const isLongBreak = !isFocus && cycleCount % 4 === 0;
  let icon, text, colorClass;
  
  if (isFocus) {
    icon = 'fa-brain';
    text = 'Focus Time';
    colorClass = 'text-primary';
  } else if (isLongBreak) {
    icon = 'fa-spa';
    text = 'Long Break';
    colorClass = 'text-info';
  } else {
    icon = 'fa-coffee';
    text = 'Short Break';
    colorClass = 'text-success';
  }
  
  sessionTypeLabel.innerHTML = `<i class="fas ${icon}"></i> ${text}`;
  sessionTypeLabel.className = `session-type fw-bold text-center ${colorClass}`;
  cycleCountLabel.textContent = cycleCount;
}

function startTimer() {
  if (isRunning) return;
  isRunning = true;
  startBtn.disabled = true;
  pauseBtn.disabled = false;
  resetBtn.disabled = false;

  let state = getState();
  if (!state || !state.startTime) {
    const duration = isFocus ? FOCUS_TIME : (cycleCount % 4 === 0 ? LONG_BREAK : SHORT_BREAK);
    state = { startTime: Date.now(), duration, isFocus, cycleCount };
  } else {
    const currentTime = parseInt(display.textContent.split(':')[0]) * 60 + parseInt(display.textContent.split(':')[1]);
    state.startTime = Date.now() - ((state.duration || FOCUS_TIME) - currentTime) * 1000;
  }
  saveState(state);
  tick();
}

function tick() {
  clearInterval(timerInterval);
  timerInterval = setInterval(() => {
    const state = getState();
    const elapsed = Math.floor((Date.now() - state.startTime) / 1000);
    const timeLeft = state.duration - elapsed;
    if (timeLeft <= 0) {
      clearInterval(timerInterval);
      sessionCompleted();
    } else {
      updateDisplay(timeLeft);
    }
  }, 1000);
}

function pauseTimer() {
  clearInterval(timerInterval);
  const state = getState();
  const elapsed = Math.floor((Date.now() - state.startTime) / 1000);
  const remaining = state.duration - elapsed;
  saveState({ duration: remaining, startTime: null, isFocus, cycleCount });
  isRunning = false;
  startBtn.disabled = false;
  pauseBtn.disabled = true;
  document.title = 'Pomodoro Timer - StudyBuddy';
}

function resetTimer() {
  clearInterval(timerInterval);
  isRunning = false;
  isFocus = true;
  cycleCount = 0;
  localStorage.removeItem('pomodoroState');
  localStorage.removeItem('pomodoroCycles');
  updateDisplay(FOCUS_TIME);
  updateSessionType();
  startBtn.disabled = false;
  pauseBtn.disabled = true;
  resetBtn.disabled = true;
  document.title = 'Pomodoro Timer - StudyBuddy';
}

// Log Focus Session to DB when completed
function logFocusSession() {
  fetch('timer.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'duration=25'
  }).then(res => res.text())
    .then(txt => console.log('Session logged:', txt))
    .catch(err => console.error(err));
}

function sessionCompleted() {
  const state = getState();
  
  // Play notification sound (browser notification)
  if ('Notification' in window && Notification.permission === 'granted') {
    new Notification('StudyBuddy Timer', {
      body: state.isFocus ? '✅ Focus session complete! Take a break 🍵' : '☕ Break finished! Time to focus again 💪',
      icon: 'favicon.ico'
    });
  }
  
  if (state.isFocus) {
    cycleCount++;
    logFocusSession();
    alert('✅ Focus session complete! Take a break 🍵');
    isFocus = false;
  } else {
    alert('☕ Break finished! Time to focus again 💪');
    isFocus = true;
  }

  const nextDuration = isFocus
    ? FOCUS_TIME
    : (cycleCount % 4 === 0 ? LONG_BREAK : SHORT_BREAK);

  const newState = {
    startTime: Date.now(),
    duration: nextDuration,
    isFocus,
    cycleCount
  };
  saveState(newState);
  updateSessionType();
  tick();
}

// Restore on load
(function restore() {
  const state = getState();
  if (!state) {
    updateDisplay(FOCUS_TIME);
    updateSessionType();
    saveState({ startTime: null, duration: FOCUS_TIME, isFocus: true, cycleCount: 0 });
    return;
  }

  isFocus = state.isFocus ?? true;
  cycleCount = state.cycleCount ?? 0;
  updateSessionType();

  if (state.startTime && !isNaN(state.startTime)) {
    const elapsed = Math.floor((Date.now() - state.startTime) / 1000);
    const timeLeft = state.duration - elapsed;

    if (timeLeft > 0) {
      updateDisplay(timeLeft);
      isRunning = true;
      startBtn.disabled = true;
      pauseBtn.disabled = false;
      resetBtn.disabled = false;
      tick();
    } else {
      sessionCompleted();
    }
  } else {
    updateDisplay(state.duration);
  }
})();

startBtn.addEventListener('click', startTimer);
pauseBtn.addEventListener('click', pauseTimer);
resetBtn.addEventListener('click', resetTimer);

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
  Notification.requestPermission();
}

// Add fade-in animation with delay
document.querySelectorAll('.fade-in').forEach((el, index) => {
  el.style.animationDelay = `${index * 0.1}s`;
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
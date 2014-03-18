# xkdl #

*xkdl* (pronounced like *schedule*) is a simple task manager, scheduler and time tracker.

## Schedule ##

The idea is that you can put a bunch of tasks with deadline and duration and the scheduler
assigns them to time slots automatically making sure that the most important task is always
the next one in the list while respecting constraints like execution time and dependencies.

The scheduler uses currently a strict earliest-deadline-first approach but other things like
difficulty, duration and custom priority could be a factor.

## Storage ##

Tasks are stored as folders with the naming schema `_[duration_]Name of task` for incomplete and `X_Name of task`
for completed tasks. Properties are saved in a file names `__.txt`, composed objects are stored in corresponding
files (e.g. `windows.txt` and `logs.txt`).
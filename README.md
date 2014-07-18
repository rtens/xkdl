# xkdl [![Build Status](https://travis-ci.org/rtens/xkdl.png?branch=master)](https://travis-ci.org/rtens/xkdl)

*xkdl* (pronounced like "*schedule*") is a simple task manager, scheduler and time tracker.

## Tasks ##

A task is *something that can be done*. Tasks are structured hierarchically, each task has
a parent and may have multiple children and certain properties are inherited from the parent
or aggregated from the children.

## Tracking ##

Tracking means just writing down time spans that have been spent working on the task. The
logs can be used for a lot of things like billing or evidence-based estimation.

## Scheduling ##

With *xkdl* you can just define a bunch of tasks with deadline and duration and the scheduler
automatically assigns them time slots making sure that the most important task is always
the next one in the list while respecting constraints like execution time and dependencies.

The scheduler uses currently a strict earliest-deadline-first approach but other things like
difficulty, duration and custom priority could be a factor.

## Storage ##

Tasks are stored as folders with the naming schema `_[duration_]Name of task` for incomplete and `X_Name of task`
for completed tasks. Properties are saved in a file names `__.txt`, composed objects are stored in corresponding
files (e.g. `windows.txt` and `logs.txt`).

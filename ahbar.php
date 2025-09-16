<?php

class Task {
    public $taskName;

    public function __construct($taskName) {
        $this->taskName = $taskName;
    }

    
    public function show() {
        echo "Mission: " . $this->taskName . "\n";
    }
}


function line() {
    echo "----------------------------------\n";
    exit;
}

function showTask($tasks, $id) {
    if (isset($tasks[$bid])) {
        $tasks[$id]->show();
    } else {
        echo "No mission found with that ID.\n";
    }
}

function showAllTasks($tasks) {
    foreach ($tasks as $task) {
        $task->show();
    }
}

function addTask(&$tasks, $taskName) {
    $tasks[] = new Task($taskName);
}

$tasks = [
    new Task("Find the princess"),
    new Task("Defeat the bowser"),
    new Task("Save the kingdom")
];

while (true) {
    line();
    $text = readline("the princess is in another castle! What do you want to do? ");
    switch ($text) {
        case "all":
            line();
            showAllTasks($task);
        break;
        case "show":
            line();
            $id = red("Which mission do you want to see? ");
            showTask($tasks, $id);
            break;
        case "add":
            line();
            $newTaskName = readline("Enter the name of the new mission: ");
            addTask($tasks, $newTaskName);
        case "help":
            line();
            echo "Available commands:\n";
            echo "all   - Show all missions\n";
            echo "leave - Exit the castle\n";
            echo "add   - Add a new mission\n";
            echo "help  - Shows all comands / this list has\n";
        break;
        case "leave":
            line();
            echo "you left the castle!\n";
            line();
            exit;
        break;

        default:
            line();
            echo "I don't understand that, what did you say?.\n";
            break;
    }
}
<?php

$tasks = [
    1 => ['status' => 'done', 'priority' => 2, 'content' => 'konsultācija 15:10'],
    2 => ['status' => 'inprogress', 'priority' => 5, 'content'=> 'aizbraukt uz veikalu 19:00'],
    3 => ['status' => 'inprogress', 'priority' => 5, 'content'=> 'aizbraukt uz veikalu 19:00'],
];

function displayTask($task) {
    echo "Priority: {$task['priority']}, CONTENT: {$task['content']}, STATUS: {$task['status']}\n";
}

function viewTask(&$tasks) {
    $id = readline("Ievadi uzdevuma ID: ");
    if (isset($tasks[$id])) {
        displayTask($tasks[$id]);
    } else {
        echo "Uzdevums nav atrasts\n";
    }
}

function addTask(&$tasks) {
    $newContent = readline("Ievadiet jaunu uzdevumu: ");
    $newPriority = readline("Ievadiet svarīgumu: ");
    $newStatus = readline("Ievadiet Status: ");
    $tasks[] = [ 'status' => $newStatus, 'priority' => $newPriority, 'content' => $newContent ];
    echo "Uzdevums pievienots\n";
}

function deleteTask(&$tasks) {
    $id = readline("Ievadiet dzēšamā uzdevuma ID: ");
    if (isset($tasks[$id])) {
        unset($tasks[$id]);
        echo "Uzdevums dzēsts\n";
    } else {
        echo "Uzdevums nav atrasts\n";
    }
}

function editTask(&$tasks) {
    $id = readline("Ievadi uzdevuma ID, kuru vēlies mainīt: ");
    if (isset($tasks[$id])) {
        $newContent = readline("Ievadīt jaunu saturu: ");
        if (!empty($newContent)) {
            $tasks[$id]['content'] = $newContent;
            echo "Uzdevums rediģēts\n";
        } else {
            echo "Saturs nav ievadīts\n";
        }
    } else {
        echo "Uzdevums nav atrasts\n";
    }
}

function setStatus(&$tasks) {
    $id = readline("Ievadi uzdevuma ID, kuram vēlies mainīt statusu: ");
    if (isset($tasks[$id])) {
        $newStatus = readline("Ievadi jauno statusu (new, done, inprogress): ");
        if (in_array($newStatus, ['new', 'done', 'inprogress'])) {
            $tasks[$id]['status'] = $newStatus;
            echo "Statuss uzstādīts\n";
        } else {
            echo "Nederīgs statusa nosaukums\n";
        }
    } else {
        echo "Uzdevums nav atrasts\n";
    }
}

function displayAllTasks(&$tasks) {
    foreach ($tasks as $task) {
        displayTask($task);
    }
}

$continue = true;

do {
    echo "UZDEVUMU PĀRVALDNIEKS\n";
    echo "Apskatīt => 1\n";
    echo "Pievienot => 2\n";
    echo "Dzēst => 3\n";
    echo "Rediģēt => 4\n";
    echo "Rādīt visus => 5\n";
    echo "Uzstādīt statusu => 6\n";
    echo "Iziet => 7\n";
    $choice = readline("Izvēlies darbības numuru: ");

    echo "==========\n";
    try {
        switch ($choice) {
            case 1:
                viewTask($tasks);
                break;
            case 2:
                addTask($tasks);
                break;
            case 3:
                deleteTask($tasks);
                break;
            case 4:
                editTask($tasks);
                break;
            case 5:
                displayAllTasks($tasks);
                break;
            case 6:
                setStatus($tasks);
                break;
            case 7:
                echo "Uz redzēšanos!\n";
                $continue = false;
                break;
            default:
                echo "Invalid option selected\n";
        }
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    echo "==========\n\n";
} while ($continue);
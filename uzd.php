<?php
$tasks = ["ghgf","sdfgfd","uiuii"];

function line(){
    echo "---------------------------\n\n";
}


function view(&$tasks){
    $id=1;
    foreach($tasks as $task){
        echo $id.". ".$task."\n";
        $id+=1;
    }
}
function add(&$tasks){
    $add = readline("add===>>>  ");
    $tasks[] =$add;
}
function destroy(&$tasks){
    $id = readline("id===>>>  ");
    unset($tasks[$id-1]);
    $tasks = array_values($tasks);
}
function update(&$tasks){
    $id = readline("id===>>>  ");
    $edit = readline("edit===>>>  ");
    $tasks[$id-1] = $edit;
}
echo "Task";
echo "1. view all\n";
echo "2. add\n";
echo "3. destroy\n";
echo "4. edit\n";
line();
while (true){
    
    $input = readline("input===>>>  ");
    line();
    if ($input == "n"){
        exit;
    }
    switch ($input) {
        case '1':
            view($tasks);
            line();
            break;
        case '2':
            add($tasks);
            line();
            break;
        case '3':
            destroy($tasks);
            line();
            break;
        case '4':
            update($tasks);
            line();
        break;
                                                      
        default:
            echo "no such function \n";
            line();
            break;
    }

}
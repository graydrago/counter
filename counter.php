<?php
define("BUF_SIZE", 4096);
define("OUT_COUNT", 5);

define("STAT_OK", 0);
define("STAT_FILE_NOT_FOUND", 1);
define("STAT_FILE_NOT_OPENED", 2);
define("STAT_FILE_READ_ERROR", 3);

declare(ticks = 1);
pcntl_signal(SIGINT, "signalHandler");

// ----- PRINT HELP MESSAGE -----

if ($argc == 1) {
    echo <<<HERE
Usage:
    {$argv[0]} [file|-]

Examples:
    {$argv[0]} some_file

    echo '10' | {$argv[0]} -

    You can use a pipe input by typing "-".

HERE;
    exit(STAT_OK);
}

// ----- ARGS -----

$fileName = $argv[1];
$file = null;
if (strcmp($fileName, '-') === 0) {
    $file = STDIN;
} else {
    if (file_exists($fileName)) {
        $file = fopen($fileName, 'rb');
        if ($file === false) {
            fprintf(STDERR, "Can't open '{$fileName}'\n");
            exit(STAT_FILE_NOT_OPENED);
        }
    } else {
        fprintf(STDERR, "File '{$fileName}' has not found.\n");
        exit(STAT_FILE_NOT_FOUND);
    }
}

// ----- MAIN -----

$STATE = 'getNumber';
$FOUND_DIGITS = [];

$read_bites = 0;
while (($data = fread($file, BUF_SIZE)) !== false) {
    $len = strlen($data);
    for ($i = 0; $i < $len; $i++) {
        $STATE($data[$i]);
        $read_bites++;
    }

    //echo "{$read_bites} bites was read\r";

    if (feof($file)) {
        break;
    }
}

if ($read_bites > 0) {
    echo "{$read_bites} bites was read\n";
}

if ($data === false) {
    fprintf(STDERR, "While reading '{$fileName}' has happened an error\n");
    printResult($FOUND_DIGITS);
    exit(STAT_FILE_READ_ERROR);
} else {
    printResult($FOUND_DIGITS);
}

// ----- FUNCTIONS -----

function skipMess($char) {
    global $STATE;

    if (ctype_space($char)) {
        $STATE = 'skipSpaces';
    }
}

function skipSpaces($char) {
    global $STATE;

    if (ctype_digit($char)) {
        $STATE = 'getNumber';
        getNumber($char);
        return;
    }

    if (!ctype_space($char)) {
        $STATE = 'skipMess';
    }
}

function getNumber($char) {
    global $STATE, $FOUND_DIGITS;

    static $buffer = [];

    if (ctype_digit($char)) {
        array_push($buffer, $char);
    } else {
        if (ctype_space($char)) {
            $digit = implode('', $buffer);

            if (isset($FOUND_DIGITS[$digit])) {
                $FOUND_DIGITS[$digit]++;
            } else {
                $FOUND_DIGITS[$digit] = 1;
            }

            $STATE = 'skipSpaces';
            $buffer = [];
        } else {
            $STATE = 'skipMess';
            $buffer = [];
        }
    } 
}

function printResult(&$resultArray) {
    arsort($resultArray);

    $i = 0;
    foreach ($resultArray as $key => $value) {
        echo "{$key} : {$value} times\n";

        $i++;
        if ($i >= OUT_COUNT) {
            break;
        }
    }
}

function signalHandler($signal) {
    global $FOUND_DIGITS;

    if ($signal === SIGINT) {
        printResult($FOUND_DIGITS);
        exit(STAT_OK);
    }
}

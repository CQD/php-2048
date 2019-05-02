#!/usr/bin/env php
<?php
include __DIR__ . '/src/classes.php';

run('testMergeRow');
run('testFetchRow');
run('testSetRow');

////////////////////////////////
function run($func)
{
    $result = $func();
    if (!$result) {
        $result = ['OK'];
        $color = "32";
    } else {
        $color = '33';
    }

    foreach ($result as $msg) {
        printf("%-20s \033[%sm%s\033[m\n", $func, $color, $msg);
    }
}

////////////////////////////////

function testSetRow()
{
    $map = new Map;
    $errors = [];

    $map->setMap([
        [   0,   2,    4,    8],
        [  16,  32,   64,  128],
        [ 256, 512, 1024, 2048],
        [   8,   4,    2,    0],
    ]);

    $datas = [
        ['horizontal', 0, [   1,   2,    3,    4]],
        ['horizontal', 1, [   5,   6,    7,    8]],
        ['horizontal', 2, [   9,  10,   11,   12]],
        ['horizontal', 3, [  13,  14,   15,   16]],
        ['vertical',   0, [   1,   2,    3,    4]],
        ['vertical',   1, [   5,   6,    7,    8]],
        ['vertical',   2, [   9,  10,   11,   12]],
        ['vertical',   3, [  13,  14,   15,   16]],
    ];

    foreach ($datas as list($direction, $idx, $value)) {
        $result1 = $map->setRow($direction, $idx, $value);
        $result2 = $map->fetchRow($direction, $idx);
        if ($result1 !== $value) {
            $errors[] = sprintf("%s setRow 的結果不是 %s 而是 %s",
                "$direction, $idx",
                implode(',', $value),
                implode(',', $result1)
            );
        }
        if ($result1 !== $result2) {
            $errors[] = sprintf("%s fetchRow 的結果不是 %s 而是 %s",
                "$direction, $idx",
                implode(',', $result1),
                implode(',', $result2)
            );
        }
    }

    return $errors;
}
function testFetchRow()
{
    $map = new Map;
    $errors = [];

    $map->setMap([
        [   0,   2,    4,    8],
        [  16,  32,   64,  128],
        [ 256, 512, 1024, 2048],
        [   8,   4,    2,    0],
    ]);

    $datas = [
        ['horizontal', 0, [   0,   2,    4,    8]],
        ['horizontal', 1, [  16,  32,   64,  128]],
        ['horizontal', 2, [ 256, 512, 1024, 2048]],
        ['horizontal', 3, [   8,   4,    2,    0]],
        ['vertical',   0, [   0,  16,  256,    8]],
        ['vertical',   1, [   2,  32,  512,    4]],
        ['vertical',   2, [   4,  64, 1024,    2]],
        ['vertical',   3, [   8, 128, 2048,    0]],
    ];

    foreach ($datas as list($direction, $idx, $output)) {
        $result = $map->fetchRow($direction, $idx);
        if ($result !== $output) {
            $errors[] = sprintf("%s 的結果不是 %s 而是 %s",
                "$direction, $idx",
                implode(',', $output),
                implode(',', $result)
            );
        }
    }

    return $errors;
}

function testMergeRow()
{
    $map = new Map;
    $errors = [];

    $datas = [
        [[0, 0, 0, 0], [0, 0, 0, 0]],
        [[0, 0, 0, 2], [2, 0, 0, 0]],
        [[0, 0, 2, 2], [4, 0, 0, 0]],
        [[2, 0, 2, 0], [4, 0, 0, 0]],
        [[2, 2, 2, 0], [4, 2, 0, 0]],
        [[0, 2, 2, 0], [4, 0, 0, 0]],
        [[2, 2, 2, 2], [4, 4, 0, 0]],
        [[0, 2, 2, 2], [4, 2, 0, 0]],
        [[0, 4, 2, 2], [4, 4, 0, 0]],
        [[0, 4, 8, 4], [4, 8, 4, 0]],
    ];

    foreach ($datas as list($input, $output)) {
        $result = $map->mergeRow($input);
        if ($result !== $output) {
            $errors[] = sprintf("%s 的結果不是 %s 而是 %s",
                implode(',', $input),
                implode(',', $output),
                implode(',', $result)
            );
        }
    }

    return $errors;
}

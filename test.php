<?php


$mid = strtotime(date('Y-m-d'));
foreach (range(21600,108000,1800) as $n) {
    $s = $mid+$n;
    print date('g:ia',$s)."   ";
    print $n."\n";
}


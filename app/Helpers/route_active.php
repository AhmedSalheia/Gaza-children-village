<?php

function is_route(String $route): bool
{
    $current = app('router')->current()->uri;
    $preg_route = '/' . str_replace(['/','*'],['\/','.*'], $route) .'$/';

    return preg_match($preg_route, $current);
}

function active(String $route) {
    if (is_route($route))
        return 'active';
    else
        return;
}

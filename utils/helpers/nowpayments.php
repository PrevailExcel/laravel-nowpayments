<?php

if (! function_exists("nowpayments"))
{
    function nowpayments() {
        
        return app()->make('laravel-nowpayments');
    }
}
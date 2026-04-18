<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::client.home')->name('home');
Route::livewire('/services', 'pages::client.services.index')->name('client.services');
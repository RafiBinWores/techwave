<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::client.home')->name('home');
Route::livewire('/services', 'pages::client.services.index')->name('client.services');
Route::livewire('/services/{slug}', 'pages::client.services.details')->name('client.services.details');

// Tools
Route::livewire('/tools', 'pages::client.tools.index')->name('client.tools.index');

// Blogs
Route::livewire('/blogs', 'pages::client.blogs.index')->name('client.blogs.index');
Route::livewire('/blogs/{slug}', 'pages::client.blogs.details')->name('client.blogs.details');
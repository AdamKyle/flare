<?php

namespace App\Admin\Controllers;

use App\Flare\Models\Item;
use App\Http\Controllers\Controller;

class ItemsController extends Controller {

    public function index() {
        return view('admin.items.items', [
            'items' => Item::all(),
        ]);
    }

    public function create() {
        return view('admin.items.manage', [
            'item' => null,
        ]);
    }

    public function edit(Item $item) {
        return view('admin.items.manage', [
            'item' => $item,
        ]);
    }

    public function delete(Item $item) {

    }
}

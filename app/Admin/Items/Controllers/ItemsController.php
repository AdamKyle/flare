<?php

namespace App\Admin\Items\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ItemsController extends Controller
{
    /**
     * Render the Admin Items application shell.
     *
     * @return View Admin Items application shell view.
     */
    public function index(): View
    {
        return view('admin.items.index');
    }

    /**
     * Redirect legacy Item edit links into the modern Admin Items application.
     *
     * @return RedirectResponse Redirect to the Admin Items application.
     */
    public function edit(): RedirectResponse
    {
        return redirect()->route('admin.items.index');
    }
}

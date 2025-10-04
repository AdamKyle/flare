<?php

namespace App\Admin\Controllers;

use App\Admin\Exports\Classes\ClassesExport;
use App\Admin\Import\Classes\ClassImport;
use App\Admin\Requests\ClassesImport;
use App\Flare\Models\GameClass;
use App\Game\Core\Values\View\ClassBonusInformation;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ClassesController extends Controller
{
    public function __construct(private readonly ClassBonusInformation $classBonusInformation) {}

    public function index()
    {
        return view('admin.classes.list');
    }

    public function show(GameClass $class)
    {
        return view('admin.classes.class', [
            'class' => $class,
            'classBonus' => $this->classBonusInformation->buildClassBonusDetailsForInfo($class->name),
        ]);
    }

    public function create()
    {
        return view('admin.classes.manage', [
            'class' => null,
            'stats' => ['str', 'dex', 'agi', 'int', 'focus', 'chr', 'dur'],
            'classes' => GameClass::pluck('name', 'id')->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        $class = GameClass::updateOrCreate(['id' => $request->id], $request->all());

        return response()->redirectToRoute('classes.class', ['class' => $class->id])->with('success', 'Class has been saved.');
    }

    public function edit(GameClass $class)
    {
        return view('admin.classes.manage', [
            'class' => $class,
            'stats' => ['str', 'dex', 'agi', 'int', 'focus', 'chr', 'dur'],
            'classes' => GameClass::pluck('name', 'id')->toArray(),
        ]);
    }

    public function exportClasses()
    {
        return view('admin.classes.export');
    }

    public function importClasses()
    {
        return view('admin.classes.import');
    }

    /**
     * @codeCoverageIgnore
     */
    public function export()
    {

        $response = Excel::download(new ClassesExport, 'game_classes.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        return $response;
    }

    /**
     * @codeCoverageIgnore
     */
    public function import(ClassesImport $request)
    {
        Excel::import(new ClassImport, $request->classes_import);

        return redirect()->back()->with('success', 'imported class data.');
    }
}

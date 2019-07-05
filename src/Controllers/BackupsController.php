<?php

namespace Varbox\Controllers;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Varbox\Traits\CanCrud;
use Varbox\Contracts\BackupModelContract;
use Varbox\Filters\BackupFilter;
use Varbox\Sorts\BackupSort;

class BackupsController extends Controller
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use CanCrud;

    /**
     * @var BackupModelContract
     */
    protected $model;

    /**
     * BackupsController constructor.
     *
     * @param BackupModelContract $model
     */
    public function __construct(BackupModelContract $model)
    {
        $this->model = $model;
    }

    /**
     * @param Request $request
     * @param BackupFilter $filter
     * @param BackupSort $sort
     * @return \Illuminate\View\View
     * @throws \Exception
     */
    public function index(Request $request, BackupFilter $filter, BackupSort $sort)
    {
        return $this->_index(function () use ($request, $filter, $sort) {
            $this->items = $this->model
                ->filtered($request->all(), $filter)
                ->sorted($request->all(), $sort)
                ->paginate(config('varbox.base.crud.per_page', 10));

            $this->title = 'Backups';
            $this->view = view('varbox::admin.backups.index');
        });
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        try {
            Artisan::call('backup:run');

            flash()->success('The record was successfully created!');
        } catch (Exception $e) {
            flash()->success($e->getMessage());
        }

        return redirect()->route('admin.backups.index');
    }

    /**
     * @param BackupModelContract $backup
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(BackupModelContract $backup)
    {
        return $this->_destroy(function () use ($backup) {
            $this->redirect = redirect()->route('admin.backups.index');

            $filesystem = Storage::disk($backup->disk);

            if ($filesystem->exists($backup->path)) {
                $filesystem->delete($backup->path);
            }

            $backup->delete();
        });
    }

    /**
     * @param BackupModelContract $backup
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(BackupModelContract $backup)
    {
        try {
            return $backup->download();
        } catch (ModelNotFoundException $e) {
            flash()->error('You are trying to download a backup archive that does not exist!', $e);
            return redirect()->route('admin.backups.index');
        }
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function clear()
    {
        try {
            foreach ($this->model->all() as $backup) {
                $filesystem = Storage::disk($backup->disk);

                if ($filesystem->exists($backup->path)) {
                    $filesystem->delete($backup->path);
                }

                $backup->delete();
            }

            flash()->success('All backups were successfully deleted!');
        } catch (ModelNotFoundException $e) {
            flash()->error('Something went wrong! Please try again.', $e);
        }

        return redirect()->route('admin.backups.index');
    }
}
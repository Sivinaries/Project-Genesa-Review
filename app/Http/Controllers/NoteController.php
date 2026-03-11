<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NoteController extends Controller
{
    public function index()
    {
        if (! Auth::check()) {
            return redirect('/');
        }

        $userCompany = Auth::user()->compani;

        if (! $userCompany) {
            return redirect()->route('addcompany');
        }

        $status = $userCompany->status;

        if ($status !== 'Settlement') {
            return redirect()->route('login');
        }

        $cacheKey = "notes_{$userCompany->id}";

        $notes = Cache::remember($cacheKey, 180, function () use ($userCompany) {
            return Note::with(['employee', 'employee.position'])
                ->where('compani_id', $userCompany->id)
                ->latest('created_at')
                ->get();
        });

        $employee = Employee::where('compani_id', $userCompany->id)
            ->with(['position', 'branch'])
            ->orderBy('name')
            ->get();

        return view('note', compact('notes', 'employee'));
    }

    public function store(Request $request)
    {
        $userCompany = auth()->user()->compani;

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'note_date' => 'required|date',
            'type' => 'required|string',
            'content' => 'required|string',
        ]);

        $note = Note::create([
            'employee_id' => $data['employee_id'],
            'note_date' => $data['note_date'],
            'type' => $data['type'],
            'content' => $data['content'],
            'compani_id' => $userCompany->id,
        ]);

        $note->load('employee');

        $this->logActivity(
            'Create Note',
            "Menambahkan catatan ({$note->content}) untuk {$note->employee->name}",
            $userCompany->id
        );

        $this->clearCache($userCompany->id);

        return redirect(route('note'))->with('success', 'Catatan berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $userCompany = auth()->user()->compani;

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'note_date' => 'required|date',
            'type' => 'required|string',
            'content' => 'required|string',
        ]);

        $note = Note::where('id', $id)
            ->where('compani_id', $userCompany->id)
            ->firstOrFail();

        $oldContent = $note->content;

        $note->update([
            'employee_id' => $data['employee_id'],
            'note_date' => $data['note_date'],
            'type' => $data['type'],
            'content' => $data['content'],
        ]);

        $note->load('employee');

        $this->logActivity(
            'Update Note',
            "Mengubah catatan '{$oldContent}' menjadi '{$note->content}' untuk {$note->employee->name}",
            $userCompany->id
        );

        $this->clearCache($userCompany->id);

        return redirect(route('note'))->with('success', 'Catatan berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $userCompany = auth()->user()->compani;

        $note = Note::where('id', $id)
            ->where('compani_id', $userCompany->id)
            ->with('employee')
            ->first();

        if (!$note) {
            return redirect(route('note'))->withErrors(['msg' => 'Catatan tidak ditemukan.']);
        }

        $oldContent = $note->content;

        $note->delete();

        $this->logActivity(
            'Delete Note',
            "Menghapus catatan '{$oldContent}' untuk {$note->employee->name}",
            $userCompany->id
        );

        $this->clearCache($userCompany->id);

        return redirect(route('note'))->with('success', 'Catatan berhasil dihapus!');
    }

    private function clearCache($companyId)
    {
        Cache::forget("notes_{$companyId}");
    }

    private function logActivity($type, $description, $companyId)
    {
        $userId  = null;
        $staffId = null;

        if (Auth::guard('staff')->check()) {
            $staffId = Auth::guard('staff')->id();
        } elseif (Auth::check()) {
            $userId = Auth::id();
        }

        ActivityLog::create([
            'user_id'       => $userId,
            'staff_id'      => $staffId,
            'compani_id'    => $companyId,
            'activity_type' => $type,
            'description'   => $description,
            'created_at'    => now(),
        ]);

        Cache::forget("activities_{$companyId}");
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class BranchController extends Controller
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

        $cacheKey = "branches_{$userCompany->id}";

        $branches = Cache::tags(['branches', "company_{$userCompany->id}"])
            ->remember($cacheKey, 180, function () use ($userCompany) {
                return $userCompany->branches;
            });

        return view('branch', compact('branches'));
    }

    public function store(Request $request)
    {
        $userCompany = auth()->user()->compani;

        $data = $request->validate([
            'name' => 'required',
            'address' => 'nullable|string',
            'phone' => 'nullable|numeric',
            'category' => 'required',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'gps_radius' => 'nullable|integer|min:100|max:50000',
        ]);

        $data['compani_id'] = $userCompany->id;
        $data['gps_radius'] = $data['gps_radius'] ?? 5000;

        $branch = Branch::create($data);

        $this->logActivity(
            'Create Branch',
            "Membuat cabang baru '{$branch->name}'",
            $userCompany->id
        );

        $this->clearCache($userCompany->id);

        return redirect(route('branch'))->with('success', 'Branch successfully created!');
    }

    // public function show($id)
    // {
    //     $userCompany = auth()->user()->compani;

    //     $branch = Branch::with('employees')
    //         ->where('id', $id)
    //         ->where('compani_id', $userCompany->id)
    //         ->firstOrFail();

    //     return response()->json([
    //         'status' => true,
    //         'data' => $branch
    //     ]);
    // }

    public function update(Request $request, $id)
    {
        $userCompany = auth()->user()->compani;

        $data = $request->validate([
            'name' => 'required',
            'address' => 'nullable|string',
            'phone' => 'nullable|numeric',
            'category' => 'required',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'gps_radius' => 'nullable|integer|min:100|max:50000',
        ]);

        $branch = Branch::where('id', $id)
            ->where('compani_id', $userCompany->id)
            ->firstOrFail();

        $oldContent = $branch->name;

        $branch->update($data);

        $this->logActivity(
            'Update Branch',
            "Mengubah Branch '{$oldContent}' menjadi '{$branch->name}'",
            $userCompany->id
        );

        $this->clearCache($userCompany->id);

        return redirect(route('branch'))->with('success', 'Branch successfully updated!');
    }

    public function destroy($id)
    {
        $userCompany = auth()->user()->compani;

        $branch = Branch::where('id', $id)
            ->where('compani_id', $userCompany->id)
            ->firstOrFail();

        $oldContent = $branch->name;

        $branch->delete();

        $this->logActivity(
            'Delete Branch',
            "Menghapus cabang '{$oldContent}'",
            $userCompany->id
        );

        $this->clearCache($userCompany->id);

        return redirect(route('branch'))->with('success', 'Branch successfully deleted!');
    }

    private function clearCache($companyId)
    {
        Cache::tags(["company_{$companyId}", 'branches'])->flush();
        Cache::tags(["company_{$companyId}", 'employees'])->flush();
        
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
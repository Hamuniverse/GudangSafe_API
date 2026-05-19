<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    /**
     * GET /api/employee
     */
    public function index(): JsonResponse
    {
        $employees = User::where('role', 'employee')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_active', 'created_at']);

        return response()->json([
            'success' => true,
            'count'   => $employees->count(),
            'data'    => $employees,
        ]);
    }

    /**
     * POST /api/employee
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $employee = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => $request->password,
            'role'      => 'employee',
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pegawai berhasil ditambahkan.',
            'data'    => $employee->only(['id', 'name', 'email', 'is_active', 'created_at']),
        ], 201);
    }

    /**
     * PUT /api/employee/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $employee = User::where('role', 'employee')->findOrFail($id);

        $request->validate([
            'name'      => 'sometimes|string|max:100',
            'email'     => ['sometimes', 'email', Rule::unique('users')->ignore($id)],
            'password'  => 'sometimes|string|min:8|confirmed',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $request->only(['name', 'email', 'is_active']);

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $employee->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Data pegawai berhasil diperbarui.',
            'data'    => $employee->fresh()->only(['id', 'name', 'email', 'is_active']),
        ]);
    }

    /**
     * DELETE /api/employee/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $employee = User::where('role', 'employee')->findOrFail($id);

        // Soft delete: nonaktifkan saja, jangan hapus data historis
        $employee->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Pegawai berhasil dinonaktifkan.',
        ]);
    }
}

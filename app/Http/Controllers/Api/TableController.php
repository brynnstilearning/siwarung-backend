<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::orderBy('number')->get();

        return response()->json([
            'data' => $tables,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|max:50|unique:tables,number',
            'capacity' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $table = Table::create([
            'number' => $request->number,
            'capacity' => $request->capacity ?? 4,
            'qr_code' => Str::uuid(),
            'status' => 'available',
        ]);

        return response()->json([
            'message' => 'Meja berhasil ditambahkan',
            'data' => $table,
        ], 201);
    }

    public function update(Request $request, Table $table)
    {
        $validator = Validator::make($request->all(), [
            'number' => 'required|string|max:50|unique:tables,number,' . $table->id,
            'capacity' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:available,occupied,reserved',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $table->update([
            'number' => $request->number,
            'capacity' => $request->capacity ?? $table->capacity,
            'status' => $request->status ?? $table->status,
        ]);

        return response()->json([
            'message' => 'Meja berhasil diperbarui',
            'data' => $table,
        ]);
    }

    public function destroy(Table $table)
    {
        $table->delete();

        return response()->json([
            'message' => 'Meja berhasil dihapus',
        ]);
    }

    public function qrImage(Table $table)
    {
        return response(
            QrCode::format('svg')->size(300)->generate($table->qr_code),
            200
        )->header('Content-Type', 'image/svg+xml');
    }

    public function scan(string $qrCode)
    {
        $table = Table::where('qr_code', $qrCode)->first();

        if (! $table) {
            return response()->json([
                'message' => 'Meja tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'data' => $table,
        ]);
    }
}
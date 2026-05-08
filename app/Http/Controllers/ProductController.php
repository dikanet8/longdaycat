<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Seed some sample data if empty for demonstration
        if (Produk::count() === 0) {
            Produk::create([
                'kode_produk' => 'PRD001',
                'nama_produk' => 'Kaos Polos Cotton',
                'gambar' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&q=80&w=200',
                'ukuran' => 'L',
                'warna' => 'Hitam',
                'harga' => 75000,
                'stok' => 100,
                'stok_minimal' => 10,
            ]);
            Produk::create([
                'kode_produk' => 'PRD002',
                'nama_produk' => 'Hoodie Premium',
                'gambar' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&q=80&w=200',
                'ukuran' => 'XL',
                'warna' => 'Navy',
                'harga' => 150000,
                'stok' => 50,
                'stok_minimal' => 5,
            ]);
        }

        $perPage = $request->input('per_page', 10);
        $products = Produk::latest()->paginate($perPage)->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => [
                'per_page' => (int)$perPage
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Products/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'kode_produk' => 'required|string|max:100|unique:produk',
            'nama_produk' => 'required|string|max:255',
            'gambar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ukuran' => 'required|in:S,M,L,XL,XXL',
            'warna' => 'required|string|max:255',
            'harga' => 'required|numeric|min:0',
            'stok' => 'required|integer|min:0',
            'stok_minimal' => 'required|integer|min:0',
        ]);

        $data = $request->all();

        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/products'), $filename);
            $data['gambar'] = '/uploads/products/' . $filename;
        }

        $product = Produk::create($data);

        ActivityLog::log('Tambah Produk', "Menambahkan produk baru: {$product->nama_produk} ({$product->kode_produk})", $product);

        return redirect()->route('products.index')->with('message', 'Produk berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $produk = Produk::findOrFail($id);
        return Inertia::render('Products/Edit', [
            'product' => $produk
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);

        $request->validate([
            'kode_produk' => 'required|string|max:100|unique:produk,kode_produk,' . $id,
            'nama_produk' => 'required|string|max:255',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ukuran' => 'required|in:S,M,L,XL,XXL',
            'warna' => 'required|string|max:255',
            'harga' => 'required|numeric|min:0',
            'stok' => 'required|integer|min:0',
            'stok_minimal' => 'required|integer|min:0',
        ]);

        $data = $request->except(['gambar']);

        if ($request->hasFile('gambar')) {
            // Delete old image
            if ($produk->gambar && file_exists(public_path($produk->gambar))) {
                @unlink(public_path($produk->gambar));
            }

            $file = $request->file('gambar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/products'), $filename);
            $data['gambar'] = '/uploads/products/' . $filename;
        }

        $produk->update($data);

        ActivityLog::log('Edit Produk', "Memperbarui data produk: {$produk->nama_produk} ({$produk->kode_produk})", $produk);

        return redirect()->route('products.index')->with('message', 'Produk berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $produk = Produk::findOrFail($id);
        
        // Delete image file if exists
        if ($produk->gambar && file_exists(public_path($produk->gambar))) {
            @unlink(public_path($produk->gambar));
        }

        ActivityLog::log('Hapus Produk', "Menghapus produk: {$produk->nama_produk} ({$produk->kode_produk})");

        $produk->delete();

        return redirect()->route('products.index')->with('message', 'Produk berhasil dihapus!');
    }
}

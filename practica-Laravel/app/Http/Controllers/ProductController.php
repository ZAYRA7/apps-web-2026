<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    private function semilla(): array
    {
        return [
            1 => ['id' => 1, 'nombre' => 'Laptop', 'categoria' => 'Tecnologia', 'precio' => 15999.00],
            2 => ['id' => 2, 'nombre' => 'Mouse', 'categoria' => 'Tecnologia', 'precio' => 299.00],
            3 => ['id' => 3, 'nombre' => 'Escritorio', 'categoria' => 'Muebles', 'precio' => 2499.00],
            4 => ['id' => 4, 'nombre' => 'Silla', 'categoria' => 'Muebles', 'precio' => 1899.00],
        ];
    }

    private function todos(Request $request): array
    {
        return $request->session()->get('products', $this->semilla());
    }

    private function guardar(Request $request, array $products): void
    {
        $request->session()->put('products', $products);
    }

    public function index(Request $request)
    {
        $products = collect($this->todos($request));
        $buscar = $request->query('buscar');
        $categoria = $request->query('categoria');

        if ($buscar) {
            $products = $products->filter(
                fn ($p) => str_contains(mb_strtolower($p['nombre']), mb_strtolower($buscar))
            );
        }

        if ($categoria) {
            $products = $products->where('categoria', $categoria);
        }

        return view('products.index', [
            'products' => $products,
            'categorias' => collect($this->todos($request))->pluck('categoria')->unique(),
            'buscar' => $buscar,
            'categoria' => $categoria,
        ]);
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:100'],
            'categoria' => ['required', 'string', 'max:50'],
            'precio' => ['required', 'numeric', 'min:0'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'precio.numeric' => 'El precio debe ser un número.',
        ]);

        $products = $this->todos($request);
        $id = (empty($products) ? 0 : max(array_keys($products))) + 1;
        $products[$id] = ['id' => $id] + $datos;
        $this->guardar($request, $products);

        return redirect()->route('products.index')->with('ok', 'Producto creado correctamente.');
    }

    public function edit(Request $request, int $id)
    {
        $products = $this->todos($request);
        abort_unless(isset($products[$id]), 404);
        return view('products.edit', ['producto' => $products[$id]]);
    }

    public function update(Request $request, int $id)
    {
        $products = $this->todos($request);
        abort_unless(isset($products[$id]), 404);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:100'],
            'categoria' => ['required', 'string', 'max:50'],
            'precio' => ['required', 'numeric', 'min:0'],
        ]);

        $products[$id] = ['id' => $id] + $datos;
        $this->guardar($request, $products);

        return redirect()->route('products.index')->with('ok', 'Producto actualizado correctamente.');
    }

    public function destroy(Request $request, int $id)
    {
        $products = $this->todos($request);
        abort_unless(isset($products[$id]), 404);
        
        unset($products[$id]);
        $this->guardar($request, $products);

        return redirect()->route('products.index')->with('ok', 'Producto eliminado.');
    }
}
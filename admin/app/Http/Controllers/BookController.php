<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Image;
use App\Models\Shelf;
use App\Models\Author;
use App\Models\Category;
use App\Exports\ExportBooks;
use App\Imports\ImportBooks;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\StoreBookRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Database\Eloquent\Builder;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $books = Book::when(request('search_query'), function (Builder $query) {
            $key = request('search_query');

            $query->where('title', 'LIKE', '%' . $key .  '%');
            $query->orWhere('ISBN', 'LIKE', '%' . $key .  '%');

            $query->orWhereHas('author', function (Builder $query) use ($key) {
                $query->where('name', 'LIKE', '%' . $key . '%');
            });

            $query->orWhereHas('category', function (Builder $query) use ($key) {
                $query->where('name', 'LIKE', '%' . $key . '%');
            });
        })
            ->with(['author', 'category', 'image', 'shelf'])
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        $books->appends(request()->all());

        return view('admin.book.index', compact('books'));
    }

    public function create()
    {
        $shelves = Shelf::all();
        $categories = Category::all();
        $authors = Author::all();
        return view('admin.book.create', compact('authors', 'categories', 'shelves'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBookRequest $request)
    {
        $book = Book::create($request->only(['title', 'ISBN', 'publisher', 'date_published', 'author_id', 'category_id', 'shelf_id']));
        if ($request->hasFile('image')) {
            $this->storeImage($request, $book->id);
        }
        return back()->with(['success' => "$book->title was stored in library"]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $book = Book::with(['author', 'category', 'image', 'shelf'])->firstWhere('id', $id);
        $authors = Author::all();
        $categories = Category::all();
        $shelves = Shelf::all();
        return view('admin.book.show', compact('book', 'authors', 'shelves', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreBookRequest $request, $id)
    {
        Book::find($id)->update($request->only(['title', 'ISBN', 'publisher', 'date_published', 'author_id', 'category_id', 'shelf_id']));

        if ($request->hasFile('image')) {
            $this->storeImage($request, $id);
        }

        return redirect()->route('books.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Book::find($id)->delete();
        return redirect()->route('books.index');
    }

    /**
     * Store books' data with importing from excel
     */
    public function import(Request $request)
    {
        Validator::make($request->all(), [
            'file' => 'required|file|mimes:xls,xlsx,csv'
        ])->validate();

        Excel::import(new ImportBooks, $request->file('file'));
        return back()->with(['success' => 'Stored books successfully']);
    }

    /**
     * export excel data
     */
    public function exportBooks()
    {
        return Excel::download(new ExportBooks, 'booklist.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    /**
     * store new book image
     * @param  \Illuminate\Http\Request  $request
     * @param int $id
     */
    private function storeImage($request, $bookId)
    {
        $newFileName = uniqid() . "_" . $request->file('image')->getClientOriginalName();

        $imageExists = Image::where('imageable_id', $bookId)->exists() ? (Image::firstWhere('imageable_id', $bookId)->imageable_type === Book::class ? true : false) : false;

        if ($imageExists) {
            // delete current file from storage
            $filename = Image::where('imageable_id', $bookId)->pluck('filename')->first();
            Storage::delete('public/' . $filename);
            // update new image
            $request->file('image')->storeAs('public', $newFileName);
            Image::where('imageable_id', $bookId)->update([
                'filename' => $newFileName
            ]);
        } else {
            $request->file('image')->storeAs('public', $newFileName);
            Image::create([
                'filename' => $newFileName,
                'imageable_id' => $bookId,
                'imageable_type' => Book::class
            ]);
        }
    }
}

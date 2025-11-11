<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Bootstrap CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <title>@yield('title', 'Admin')</title>
</head>


<body>
    <div class="container-fluid p-0">
        <div class="row g-0">

            {{-- サイドバー --}}
            <aside
                class="col-12 col-md-3 col-lg-2 d-flex flex-column align-items-center py-4
                vh-100 position-sticky top-0"
                style="background-color:#9CDBE2;">
                <div class="mb-4">
                    <img src="{{ asset('images/HELLO2.png') }}" alt="Hello World"
                        class="img-fluid rounded-circle" style="max-width:150px;">
                </div>

                <nav class="nav flex-column w-100 px-4 fw-bold">
                    {{-- Home --}}
                    <a class="nav-link text-dark" href="{{ route('admin.dashboard') }}">Home</a>

                    {{-- Students --}}
                    <a class="nav-link text-dark" href="{{ route('admin.students.index') }}">Students</a>

                    {{-- Teachers --}}
                    <a class="nav-link text-dark" href="{{ route('admin.teachers.index') }}">Teachers</a>

                    {{-- Courses --}}
                    <a class="nav-link text-dark" href="{{ route('admin.courses.index') }}">Courses</a>

                </nav>

                <div class="mt-auto text-left w-100 px-5">
                    <div class="small fw-bold">{{ Auth::user()->name}}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-link text-danger fw-bold p-0">Logout</button>
                    </form>
                </div>
            </aside>

            {{-- メイン --}}
            <main class="col bg-light p-0">
                <div class="bg-white p-4 rounded shadow-sm h-100">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

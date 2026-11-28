<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Promotions | Student Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-authenticated-navigation />

    <main class="students-page">
        <header class="students-page__header">
            <div>
                <h1>Student Promotions</h1>
                <p>View student academic progression history.</p>
            </div>

            @can('create', \App\Models\StudentPromotion::class)
                <a href="{{ route('student-promotions.create') }}"
                   class="students-page__button">
                    Promote Student
                </a>
            @endcan
        </header>

        @if (session('success'))
            <div class="student-edit-form__success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($promotions->isEmpty())
            <section class="students-page__empty">
                <h2>No Promotions Recorded</h2>
                <p>No student progression records are available yet.</p>
            </section>
        @else
            <div class="students-page__table-wrapper">
                <table class="students-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Previous Class</th>
                            <th>Destination Class</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($promotions as $promotion)
                            <tr>
                                <td>
                                    {{ $promotion->student?->student_number ?? 'Unavailable' }}
                                </td>
                                <td>
                                    {{ $promotion->sourceEnrollment?->academicClass?->name ?? 'Unavailable' }}
                                </td>
                                <td>
                                    {{ $promotion->destinationEnrollment?->academicClass?->name ?? 'Unavailable' }}
                                </td>
                                <td>
                                    {{ ucfirst($promotion->promotion_type) }}
                                </td>
                                <td>
                                    {{ $promotion->promotion_date->format('d M Y') }}
                                </td>
                                <td>
                                    <a
                                        href="{{ route('student-promotions.show', $promotion) }}"
                                        class="students-table__view"
                                    >
                                        View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="students-page__pagination">
                {{ $promotions->links() }}
            </div>
        @endif
    </main>
</body>
</html>
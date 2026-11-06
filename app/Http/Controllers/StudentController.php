<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display active students with optional search.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $students = Student::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = "%{$search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->where('student_number', 'like', $term)
                        ->orWhere('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('students.index', compact('students', 'search'));
    }

    /**
     * Display archived students.
     */
    public function archived(): View
    {
        $students = Student::onlyTrashed()
            ->latest('deleted_at')
            ->paginate(10);

        return view('students.archived', compact('students'));
    }

    /**
     * Restore an archived student.
     */
    public function restore(Student $student): RedirectResponse
    {
        abort_unless($student->trashed(), 404);

        $student->restore();

        return to_route('students.show', $student)
            ->with('success', 'Student restored successfully.');
    }

    /**
     * Display an individual student's profile.
     */
    public function show(Student $student): View
    {
        return view('students.show', compact('student'));
    }

    /**
     * Display the student editing form.
     */
    public function edit(Student $student): View
    {
        return view('students.edit', compact('student'));
    }

    /**
     * Update an existing student's information.
     */
    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'student_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('students', 'student_number')
                    ->ignore($student->id),
            ],

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'enrollment_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $student->update($validated);

        return to_route('students.show', $student)
            ->with('success', 'Student information updated successfully.');
    }

    /**
     * Archive a student using soft deletion.
     */
    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return to_route('students.index')
            ->with('success', 'Student archived successfully.');
    }

    /**
     * Display the student registration form.
     */
    public function create(): View
    {
        return view('students.create');
    }

    /**
     * Store a newly registered student.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_number' => [
                'required',
                'string',
                'max:20',
                'unique:students,student_number',
            ],

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'enrollment_date' => ['required', 'date'],
        ]);

        Student::create($validated);

        return to_route('students.create')
            ->with('success', 'Student registered successfully.');
    }
}
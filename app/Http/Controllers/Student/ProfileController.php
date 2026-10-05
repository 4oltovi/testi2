<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\AvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private AvatarService $avatars
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $student = $user->student;

        return view('student.profile', compact('user', 'student'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $user->update($validated);

        return back()->with('success', 'Профил бо муваффақият навсозӣ шуд.');
    }

    /**
     * Акси профили донишҷӣ навсозӣ мекунад.
     *
     * Донишҷӣ танҳо акси худро тағйир дода метавонад — ID-и ӯ аз
     * ҳақиқати воридшавӣ гирифта мешавад, на аз дархости мушаххас.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        $student = $this->currentStudent($request);

        $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:'.implode(',', AvatarService::ALLOWED_MIMES),
                'max:'.AvatarService::MAX_KILOBYTES,
                'dimensions:min_width=1,min_height=1,max_width=12000,max_height=12000',
            ],
        ], [], ['photo' => 'Акс']);

        $oldPhoto = $student->user->avatar;

        try {
            $filename = $this->avatars->store($request->file('photo'));
        } catch (\Throwable $e) {
            // Акси ҷорӣ дастнохӣ мемонад — коркард нӯҳият мешавад
            report($e);

            throw ValidationException::withMessages([
                'photo' => 'Акси боршуда коркард нашуд. Лутфан тасдиқи онро боз аз нав тафтиш кунед.',
            ]);
        }

        $student->user->update(['avatar' => $filename]);

        // Акси кӯҳна танҳо баъди муваффақияти нав хазф мешавад
        $this->avatars->delete($oldPhoto);

        return back()->with('success', 'Акси профил бо муваффақият навсозӣ шуд.');
    }

    /**
     * Акси хустуниро аз даст мебарад ва ба ҳолти пеш бармегардад.
     */
    public function removePhoto(Request $request): RedirectResponse
    {
        $student = $this->currentStudent($request);
        $oldPhoto = $student->user->avatar;

        $student->user->update(['avatar' => null]);

        $this->avatars->delete($oldPhoto);

        return back()->with('success', 'Акси профил нест карда шуд.');
    }

    /**
     * Донишҷӯи воридшуда — танҳо аз рӯи муносибати корбари воридшуда.
     */
    private function currentStudent(Request $request): Student
    {
        $student = $request->user()?->student;

        if (! $student) {
            abort(403, 'Профили донишҷӯ ёфт нашуд.');
        }

        return $student;
    }
}

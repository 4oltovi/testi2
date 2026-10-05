<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Кадр ба панели `admin/*` ворид шуда наметавонад.
 *
 * Кадр панели худро дар `routes/hr.php` дорад. Ҳамаи модулҳои админ
 * (донишҷӯён, баҳо, гурӯҳҳо, ҳисоботҳо, танзимот, аудит, ҳисобобар...) барои
 * ӯ пӯшида анд: Кадр танҳо кормандро идора мекунад ва донишҷӯро танҳо
 * барои тасдиқи шахсияти мебинад.
 *
 * Ин навъи middleware кураи ҳамлаи «кадр ба воситаи роҳи админ гузашта идора
 * кунад» аст. Боз ҳам дар `HrPolicy` ҳамаи ҳуқуқҳои донишҷӯ `false` аст, ва
 * ҳар як action-и муҳим дар `StudentController` худ низ санҷ мешавад — се
 * қабат, ки агар якеи онҳо хато шавад, ду ҳуқуқҳои дигар ҳам ҳифзанд.
 */
class DenyHrAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isHr()) {
            abort(403, 'Барои нақши «Кадр» ин бахш дастгирии намешавад.');
        }

        return $next($request);
    }
}
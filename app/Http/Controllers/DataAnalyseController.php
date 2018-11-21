<?

namespace App\Http\Controllers;

class DataAnalyseController extends Controller
{
    public function statsTitle()
    {
        $appid = $this->request->input('appid');
        return getGzhStatic($appid);
    }
}
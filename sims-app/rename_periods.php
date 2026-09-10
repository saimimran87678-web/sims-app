$periods = \App\Models\PeriodConfig::orderBy('period_no')->get();
$i = 1;
foreach ($periods as $p) {
    echo "Updating period_no {$p->period_no} from '{$p->label}' to 'Period {$i}'\n";
    $p->label = 'Period ' . $i;
    $p->is_assembly = false;
    $p->save();
    $i++;
}
echo "Done.\n";

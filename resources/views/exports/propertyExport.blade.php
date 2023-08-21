<table>
    <tbody>
    <tr>
        <td align="center" valign="center" height="50px" style="font-weight: bold;">{{ $exportData[0][0] }}</td>
        <td align="center" valign="center" height="50px" style="font-weight: bold;">{{ $exportData[0][1] }}</td>
        <td align="center" valign="center" height="50px" style="font-weight: bold;">{{ $exportData[0][2] }}</td>
        <td align="center" valign="center" height="50px" style="font-weight: bold;">{{ $exportData[0][3] }}</td>
        <td align="center" valign="center" height="50px" style="font-weight: bold;">{{ $exportData[0][4] }}</td>
        <td align="center" valign="center" height="50px" style="font-weight: bold;">{{ $exportData[0][5] }}</td>
        <td align="center" valign="center" height="50px" style="font-weight: bold;">{{ $exportData[0][6] }}</td>
        <td align="center" valign="center" height="50px" style="font-weight: bold;">{{ $exportData[0][7] }}</td>
        <td align="center" valign="center" height="50px" style="font-weight: bold;">{{ $exportData[0][8] }}</td>
    </tr>
    @foreach($exportData as $k => $item)
        @if($k > 0)
            <tr>
                <td align="left" valign="center" height="50px" style="width: 200px; word-wrap:break-word;">{{ $item[0] }}</td>
                <td align="left" valign="center" height="50px" style="width: 200px; word-wrap:break-word;">{{ $item[1] }}</td>
                <td align="left" valign="center" height="50px" style="width: 200px; word-wrap:break-word;">{{ $item[2] }}</td>
                <td align="left" valign="center" height="50px" style="width: 200px; word-wrap:break-word;">{{ $item[3] }}</td>
                <td align="left" valign="center" height="50px" style="width: 200px; word-wrap:break-word;">{{ $item[4] }}</td>
                <td align="left" valign="center" height="50px" style="width: 100px; word-wrap:break-word;">{{ $item[5] }}</td>
                <td align="left" valign="center" height="50px" style="width: 100px; word-wrap:break-word;">{{ $item[6] }}</td>
                <td align="left" valign="center" height="50px" style="width: 100px; word-wrap:break-word;">{{ $item[7] }}</td>
                <td align="left" valign="center" height="50px" style="width: 200px; word-wrap:break-word;">{{ $item[8] }}</td>
            </tr>
        @endif
    @endforeach
    </tbody>
</table>

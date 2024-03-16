<table>
    <tbody>
        <tr>
            <td style="box-sizing:border-box;padding:25px 0;text-align:center">
                <a href="{{ config('app.url') }}" style="box-sizing:border-box;color:#bbbfc3;font-size:19px;font-weight:bold;text-decoration:none" target="_blank">
                    TLCMap Gazetteer of Historical Australian Places
                </a>
            </td>
        </tr>

        <tr>
            <td width="100%" cellpadding="0" cellspacing="0" style="border-bottom:1px solid #edeff2;border-top:1px solid #edeff2;margin:0;padding:0;width:100%">
                <table align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="margin:0 auto;padding:0;width:570px">
                    <tbody>
                        <tr>
                            <td style="padding:35px">
                                Hello!
                                <br>
                                <br>
                                TLCMap user {{$senderemail}} has sent you an invite to collaborate on their dataset as a {{$dsrole}}.
                                <br>
                                <br>
                                Please use the following link to join their dataset: <a href="{{$sharelink}}">{{$sharelink}}</a>
                                <br>
                                <br>
                                <br>
                                This is an automated message from <a href="tlcmap.org">TLCMap</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>

        <tr>
            <td>
                <table align="center" width="570" cellpadding="0" cellspacing="0" role="presentation" style="margin:0 auto;padding:0;text-align:center;width:570px">
                    <tbody>
                        <tr>
                            <td align="center" style="padding:35px">
                                <p style="line-height:1.5em;margin-top:0;color:#aeaeae;font-size:12px;text-align:center">© 2023 TLCMap Gazetteer of Historical Australian Places. All rights reserved.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>
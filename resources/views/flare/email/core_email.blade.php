<mjml>
    <mj-body>

        <!-- Company Header -->
        <mj-section background-color="#f0f0f0">
        <mj-column>
            <mj-text  font-style="bold"
                        font-size="20px"
                        color="#626262"
                        align="center">
                {{$title}}
            </mj-text>
            </mj-column>
        </mj-section>


        <!-- Introduction Text -->
        <mj-section background-color="#fafafa">
            @yield('content')
            
        </mj-section>

        <!-- Social icons -->
        @if (!is_null($showBottomText))
            @if ($showBottomText)
                <mj-section background-color="#f0f0f0">
                    <mj-text color="#525252" padding="10px 5px 5px 10px">
                        Do not respond to this email. This was an automated message. If you would like to not recieve this email in the future. Please <a href="{{route('login')}}">login</a> and 
                        head to your settings section. From there you can disable specific types of emails.
                    </mj-text>
                </mj-section>
            @endif
        @endif
    </mj-body>
</mjml>
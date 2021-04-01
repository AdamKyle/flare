<mjml>
    <mj-body>

        <!-- Header -->
        <mj-section background-color="#8db9e0">
            <mj-column>
                <mj-text  font-style="bold"
                            font-size="24px"
                            color="#3b3b3b"
                            align="center">
                    {{$title}}
                </mj-text>
            </mj-column>
        </mj-section>


        <!-- Core Text -->
        <mj-section background-color="#1a62a1">
            @yield('content')
            
        </mj-section>

        <!-- Footer Text -->
        @if (!is_null($showBottomText))
            @if ($showBottomText)
                <mj-section background-color="#8db9e0">
                    <mj-column>
                        <mj-text color="#3b3b3b" padding="10px 5px 5px 10px">
                            Do not respond to this email. This was an automated message. If you would like to not recieve this email in the future. Please <a href="{{route('login')}}">login</a> and 
                            head to your settings section. From there you can disable specific types of emails.
                        </mj-text>

                        <mj-text color="#3b3b3b" padding="10px 5px 5px 10px" font-style="italic">
                            Your email is safe with us. We will never use it for anything other then game related information.
                        </mj-text>
                    <mj-column>
                </mj-section>
            @endif
        @endif
    </mj-body>
</mjml>
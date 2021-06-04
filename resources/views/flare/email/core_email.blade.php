<mjml owa="desktop">
    <mj-head>
        <mj-title>Planes of Tlessa</mj-title>
        <mj-preview>{{$title}}</mj-preview>
        <mj-attributes>
            <mj-all font-family="'Helvetica Neue', Helvetica, Arial, sans-serif"></mj-all>
            <mj-text font-weight="400" font-size="16px" color="#000000" line-height="24px" font-family="'Helvetica Neue', Helvetica, Arial, sans-serif"></mj-text>
        </mj-attributes>
        <mj-style inline="inline">
            .body-section {
            -webkit-box-shadow: 1px 4px 11px 0px rgba(0, 0, 0, 0.15);
            -moz-box-shadow: 1px 4px 11px 0px rgba(0, 0, 0, 0.15);
            box-shadow: 1px 4px 11px 0px rgba(0, 0, 0, 0.15);
            }
        </mj-style>
        <mj-style inline="inline">
            .text-link {
            color: #5e6ebf
            }
        </mj-style>
        <mj-style inline="inline">
            .footer-link {
            color: #888888
            }
        </mj-style>

    </mj-head>
    <mj-body background-color="#E7E7E7" width="600px">
        <mj-section full-width="full-width" background-color="#167C1F" padding-bottom="0">
            <mj-column width="100%">
                <mj-text color="#ffffff" font-weight="bold" align="center" text-transform="uppercase" font-size="36px" letter-spacing="1px" padding-top="30px" padding-bottom="30px">
                    Planes Of Tlessa
                </mj-text>
                <mj-text color="#fff" align="center" font-size="13px" padding-top="0" font-weight="bold" text-transform="uppercase" letter-spacing="1px" line-height="20px">
                    Something happend while you were away.
                </mj-text>

            </mj-column>
        </mj-section>

        <mj-wrapper padding-top="0" padding-bottom="0" css-class="body-section">
            <mj-section background-color="#ffffff" padding-left="15px" padding-right="15px">
                <mj-column width="100%">
                    <mj-text color="#212b35" font-weight="bold" font-size="20px">
                        {{$title}}
                    </mj-text>
                    @yield('content')
                </mj-column>
            </mj-section>

            <mj-section background-color="#ffffff" padding-left="15px" padding-right="15px" padding-top="0">
                <mj-column width="100%">
                    <mj-divider border-color="#DFE3E8" border-width="1px" />
                </mj-column>
            </mj-section>
        </mj-wrapper>
        @if (!is_null($showBottomText))
            @if ($showBottomText)
                <mj-wrapper full-width="full-width">
                    <mj-section>
                        <mj-column width="100%" padding="0">
                            <mj-text color="#445566" font-size="11px" font-weight="bold" align="center">
                                Your email is safe with Planes of Tlessa
                            </mj-text>
                            <mj-text color="#445566" font-size="11px" align="center" line-height="16px">
                                If at any time you do not want to receive these, login and head to your settings page and make the appropriate adjustments.
                            </mj-text>
                            <mj-text color="#445566" font-size="11px" align="center" line-height="16px">
                                Please do not respond to this email. These are automated emails.
                            </mj-text>
                        </mj-column>
                    </mj-section>
                </mj-wrapper>
            @endif
        @endif

    </mj-body>
</mjml>

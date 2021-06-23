<div class="row justify-content-center">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row pb-2">
                    <x-data-tables.per-page wire:model="perPage" />
                    <x-data-tables.search wire:model="search" />
                </div>
                <x-data-tables.table :collection="$users">
                    <x-data-tables.header>
                            <x-data-tables.header-row
                                wire:click.prevent="sortBy('id')"
                                header-text="User ID"
                                sort-by="{{$sortBy}}"
                                sort-field="{{$sortField}}"
                                field="id"
                            />

                            <x-data-tables.header-row
                                wire:click.prevent="sortBy('characters.name')"
                                header-text="Character Name"
                                sort-by="{{$sortBy}}"
                                sort-field="{{$sortField}}"
                                field="characters.name"
                            />

                            <x-data-tables.header-row
                                wire:click.prevent="sortBy('characters.level')"
                                header-text="Character Level"
                                sort-by="{{$sortBy}}"
                                sort-field="{{$sortField}}"
                                field="characters.level"
                            />

                            <x-data-tables.header-row
                                wire:click.prevent="sortBy('is_banned')"
                                header-text="Is Banned"
                                sort-by="{{$sortBy}}"
                                sort-field="{{$sortField}}"
                                field="is_banned"
                            />

                            <x-data-tables.header-row
                                wire:click.prevent="sortBy('unbanned_at')"
                                header-text="Unbanned At"
                                sort-by="{{$sortBy}}"
                                sort-field="{{$sortField}}"
                                field="unbanned_at"
                            />

                            <x-data-tables.header-row
                                wire:click.prevent="sortBy('is_silenced')"
                                header-text="Is Silenced"
                                sort-by="{{$sortBy}}"
                                sort-field="{{$sortField}}"
                                field="is_silenced"
                            />

                            <x-data-tables.header-row
                                wire:click.prevent="sortBy('can_talk_again_at')"
                                header-text="Can Talk Again At"
                                sort-by="{{$sortBy}}"
                                sort-field="{{$sortField}}"
                                field="can_talk_again_at"
                            />

                            <x-data-tables.header-row
                                header-text="Actions"
                            />
                    </x-data-tables.header>
                    <x-data-tables.body>
                        @forelse($users as $user)
                            <tr wire:loading.class.delay="text-muted" class="{{!is_null($user->un_ban_request) ? 'un-ban-request' : ''}}">
                                <td>{{$user->id}}</td>
                                <td>
                                    <a href="{{route('users.user', [
                                        'user' => $user->id
                                    ])}}">{{$user->character->name}} @if (!is_null($user->un_ban_request)) <i class="fas fa-envelope"></i> @endif</a>
                                </td>
                                <td>{{$user->character->level}}</td>
                                <td>{{$user->is_banned ? 'Yes' : 'No'}}</td>
                                <td>
                                        @if ($user->is_banned && is_null($user->unbanned_at))
                                            For ever
                                        @elseif($user->is_banned && !is_null($user->unbanned_at))
                                            {{ $user->unbanned_at->format('l jS \\of F Y h:i:s A') }}
                                        @else
                                            N/A
                                        @endif
                                </td>
                                <td>{{$user->is_silenced ? 'Yes' : 'No'}}</td>
                                <td>
                                    @if ($user->is_silenced)
                                        {{ $user->can_speak_again_at->format('l jS \\of F Y h:i:s A') }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="clearfix">

                                    @if (!$user->is_banned)

                                        <x-forms.button-with-form
                                            formRoute="{{route('user.reset.password', ['user' => $user->id])}}"
                                            formId="{{'un-ban-user-' . $user->id}}"
                                            buttonTitle="Reset Password"
                                            class="btn btn-primary btn-sm"
                                        />

                                        <x-forms.button-with-form
                                            formRoute="{{route('user.force.name.change', ['user' => $user->id])}}"
                                            formId="{{'force-name-change-' . $user->id}}"
                                            buttonTitle="Force Name Change"
                                            class="btn btn-primary btn-sm"
                                        />

                                        <x-forms.drop-downs.base
                                            floatLeft="true"
                                            btnType="danger"
                                            btnSize="btn-sm"
                                            dropDownId="silence-user"
                                            dropDownTitle="Silence"
                                        >

                                            <x-forms.button-with-form
                                                formRoute="{{ route('user.silence', ['user' => $user->id]) }}"
                                                formId="{{'silence-user-10-' . $user->id}}"
                                                buttonTitle="10 Minutes"
                                                class="dropdown-item"
                                            >
                                                <input type="hidden" name="silence_for" value="10">
                                            </x-forms.button-with-form>

                                            <x-forms.button-with-form
                                                formRoute="{{ route('user.silence', ['user' => $user->id]) }}"
                                                formId="{{'silence-user-30-' . $user->id}}"
                                                buttonTitle="30 Minutes"
                                                class="dropdown-item"
                                            >
                                                <input type="hidden" name="silence_for" value="30">
                                            </x-forms.button-with-form>

                                            <x-forms.button-with-form
                                                formRoute="{{ route('user.silence', ['user' => $user->id]) }}"
                                                formId="{{'silence-user-60-' . $user->id}}"
                                                buttonTitle="60 Minutes"
                                                class="dropdown-item"
                                            >
                                                <input type="hidden" name="silence_for" value="60">
                                            </x-forms.button-with-form>

                                        </x-forms.drop-downs.base>

                                        <x-forms.drop-downs.base
                                            floatLeft="true"
                                            btnType="danger"
                                            btnSize="btn-sm"
                                            dropDownId="ban-user"
                                            dropDownTitle="Ban"
                                        >

                                            <x-forms.button-with-form
                                                formRoute="{{ route('ban.user', ['user' => $user->id]) }}"
                                                formId="{{'ban-user-1d-' . $user->id}}"
                                                buttonTitle="1 Day"
                                                class="dropdown-item"
                                            >
                                                <input type="hidden" name="ban_for" value="one-day">
                                            </x-forms.button-with-form>

                                            <x-forms.button-with-form
                                                formRoute="{{ route('ban.user', ['user' => $user->id]) }}"
                                                formId="{{'ban-user-1w-' . $user->id}}"
                                                button-title="1 Week"
                                                class="dropdown-item"
                                            >
                                                <input type="hidden" name="ban_for" value="one-week">
                                            </x-forms.button-with-form>

                                            <x-forms.button-with-form
                                                formRoute="{{ route('ban.user', ['user' => $user->id]) }}"
                                                formId="{{'ban-user-perm-' . $user->id}}"
                                                buttonTitle="For ever"
                                                class="dropdown-item"
                                            >
                                                <input type="hidden" name="ban_for" value="perm">
                                            </x-forms.button-with-form>

                                        </x-forms.drop-downs.base>
                                    @else
                                        <x-forms.button-with-form
                                            formRoute="{{route('unban.user', ['user' => $user->id])}}"
                                            formId="{{'un-ban-user-' . $user->id}}"
                                            buttonTitle="Unban User"
                                            class="btn btn-primary btn-sm"
                                        />
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-data-tables.no-results colspan="7" />
                        @endforelse
                    </x-data-tables.body>
                </x-data-tables.table>

            </div>
        </div>
    </div>
</div>

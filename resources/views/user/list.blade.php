@extends('layouts.app')
@section('content')
<div class="container">

    <table class="table">
        <thead class="thead-dark">
            <tr>
                <th scope="col">ID</th>
                <th scope="col">First Name</th>
                <th scope="col">Last Name</th>
                <th scope="col">Email</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>{{ $user->first_name }}</td>
                <td>{{ $user->last_name }}</td>
                <td>{{ $user->email }}</td>
                <td><form method="POST" action="/user/update_user_status/{{ $user->id }}">
                        @csrf

                        @if($user->status == 1)

                            {{--<input checked data-toggle="toggle" name="show" type="checkbox" >--}}
                            <input checked type="checkbox" name="status_change" data-toggle="toggle" data-on="Active" data-off="Inactive" data-onstyle="success" data-offstyle="danger" data-width="90"  data-height="35">
                        @else
                            {{--<input  data-toggle="toggle" name="show" type="checkbox" >--}}
                            <input type="checkbox" name="status_change" data-toggle="toggle" data-on="Active" data-off="Inactive" data-onstyle="success" data-offstyle="danger" data-width="90"  data-height="35">
                        @endif

                        <button type="submit" class="btn btn-default btn-sm">Change</button>

                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>


    {{ $users->links() }}

</div>


@endsection
<script type="text/javascript">
    $( document ).ready(function() {
        $('#status_change').click(function() {
            alert(1);
            alert("Checkbox state = " + $('#status_change').is(':checked'));
        });
    });
</script>
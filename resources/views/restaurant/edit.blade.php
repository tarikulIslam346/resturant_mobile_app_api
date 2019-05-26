@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Restaurant Edit</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif
                    You are logged in!

                        <form method="post" {{action('RestaurantController@update', $id)}} enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="form-group col-md-4">
                                    <label for="Name">Name:</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="form-group col-md-4">
                                    <label for="Email">Email:</label>
                                    <input type="text" class="form-control" name="email" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="form-group col-md-4">
                                    <label for="Number">Phone Number:</label>
                                    <input type="text" class="form-control" name="number" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="form-group col-md-4">
                                    <input type="file" name="filename" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="form-group col-md-4">
                                    <strong>Date : </strong>
                                    <input class="date form-control"  type="text" id="datepicker" name="date" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="form-group col-md-4">
                                    <lable>Passport Office</lable>
                                    <select name="office">
                                        <option value="Mumbai">Mumbai</option>
                                        <option value="Chennai">Chennai</option>
                                        <option value="Delhi">Delhi</option>
                                        <option value="Bangalore">Bangalore</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="form-group col-md-4" style="margin-top:60px">
                                    <button type="submit" class="btn btn-success">Submit</button>
                                </div>
                            </div>
                        </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

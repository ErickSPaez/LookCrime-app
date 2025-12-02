@if (count($errors) > 0)
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Address -->
<div class="row">
	<div class="col-12 textarea-lang">
		<label for="address">{{ Lang::get('pages.address') }}</label>
	</div>
	<div class="col-xl-12 col-sm-8 textarea-edit" style="width:100%">
		<input type="text" name="address" id="address" value="{{ old('address', isset($contact) ? $contact->address : '') }}" style="width:100%" class="form-control">
	</div>
</div>


<!-- Office -->
<div class="row">
	<div class="col-12 textarea-lang">
		<label for="office">{{ Lang::get('pages.office') }}</label>
	</div>
    <div class="col-xl-12 col-sm-8 textarea-edit" style="width:100%">
		<input type="text" name="office" id="office" value="{{ old('office', isset($contact) ? $contact->office : '') }}" style="width:100%" class="form-control">
	</div>
</div>

<!-- Phone Number -->
<div class="row">
	<div class="col-12 textarea-lang">
		<label for="phone">{{ Lang::get('pages.phone') }}</label>
	</div>
    <div class="col-xl-12 col-sm-8 textarea-edit" style="width:100%">
		<input type="text" name="phone" id="phone" value="{{ old('phone', isset($contact) ? $contact->phone : '') }}" style="width:100%" class="form-control">
	</div>
</div>

<!-- Email -->
<div class="row">
	<div class="col-12 textarea-lang">
		<label for="email">{{ Lang::get('pages.email') }}</label>
	</div>
    <div class="col-xl-12 col-sm-8 textarea-edit" style="width:100%">
		<input type="text" name="email" id="email" value="{{ old('email', isset($contact) ? $contact->email : '') }}" style="width:100%" class="form-control">
	</div>
</div>

<!-- Submit -->
<div class="row">
	<div class="col-12 submit-text">
		<button type="submit" class="btn btn-lookcrim">{{ Lang::get('buttons.submit') }}</button>
	</div>
</div>

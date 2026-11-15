<div class="col-md-4">
    <label class="form-label">حداقل طول</label>
    <input type="number" min="0" wire:model="fields.{{ $i }}.settings.min_length" class="form-control @error("fields.$i.settings.min_length") is-invalid @enderror">
    @error("fields.$i.settings.min_length") <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="col-md-4">
    <label class="form-label">حداکثر طول</label>
    <input type="number" min="1" wire:model="fields.{{ $i }}.settings.max_length" class="form-control @error("fields.$i.settings.max_length") is-invalid @enderror">
    @error("fields.$i.settings.max_length") <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

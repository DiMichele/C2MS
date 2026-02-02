<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\OrganizationalUnit;

/**
 * Form Request per la validazione delle unità organizzative.
 * 
 * Implementa validazioni specifiche:
 * - Nomi duplicati sotto stesso parent sono bloccati
 * - Spostamenti che creerebbero cicli sono bloccati
 * - Verifiche di permesso
 */
class StoreOrganizationalUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('gerarchia.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Ottieni l'ID dell'unità se stiamo aggiornando
        $unitId = $this->route('uuid') ?? $this->route('unit')?->id ?? null;
        
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'type_id' => [
                'required',
                'exists:organizational_unit_types,id',
            ],
            'parent_id' => [
                'nullable',
                'uuid',
                'exists:organizational_units,id',
                // Non può essere parent di se stesso
                Rule::notIn([$unitId]),
            ],
            'parent_uuid' => [
                'nullable',
                'uuid',
                'exists:organizational_units,id',
                Rule::notIn([$unitId]),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
            ],
            'acronym' => [
                'nullable',
                'string',
                'max:20',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_active' => [
                'boolean',
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateUniqueNameUnderParent($validator);
            $this->validateNoCycle($validator);
        });
    }

    /**
     * Valida che non esistano nomi duplicati sotto lo stesso parent.
     */
    protected function validateUniqueNameUnderParent($validator): void
    {
        $parentId = $this->input('parent_id') ?? $this->input('parent_uuid');
        $name = $this->input('name');
        $unitId = $this->route('uuid') ?? $this->route('unit')?->id;
        
        if (!$name) {
            return;
        }

        $query = OrganizationalUnit::where('parent_id', $parentId)
            ->where('name', $name);
        
        if ($unitId) {
            $query->where('id', '!=', $unitId);
        }

        if ($query->exists()) {
            $validator->errors()->add(
                'name', 
                'Esiste già un\'unità con questo nome sotto lo stesso parent.'
            );
        }
    }

    /**
     * Valida che lo spostamento non crei un ciclo nella gerarchia.
     */
    protected function validateNoCycle($validator): void
    {
        $unitId = $this->route('uuid') ?? $this->route('unit')?->id;
        $newParentId = $this->input('parent_id') ?? $this->input('parent_uuid');
        
        // Solo per update con cambio parent
        if (!$unitId || !$newParentId) {
            return;
        }

        // Verifica che il nuovo parent non sia un discendente dell'unità
        if ($this->wouldCreateCycle($unitId, $newParentId)) {
            $validator->errors()->add(
                'parent_id',
                'Impossibile spostare: creerebbe un ciclo nella gerarchia.'
            );
        }
    }

    /**
     * Verifica se lo spostamento creerebbe un ciclo.
     */
    protected function wouldCreateCycle(string $unitId, string $newParentId): bool
    {
        $unit = OrganizationalUnit::find($unitId);
        
        if (!$unit) {
            return false;
        }

        // Ottieni tutti i discendenti dell'unità
        $descendantIds = $this->getDescendantIds($unit);
        
        // Se il nuovo parent è tra i discendenti, creerebbe un ciclo
        return in_array($newParentId, $descendantIds);
    }

    /**
     * Ottiene ricorsivamente tutti gli ID dei discendenti.
     */
    protected function getDescendantIds(OrganizationalUnit $unit): array
    {
        $ids = [];
        
        // Usa la closure table se disponibile
        $descendants = $unit->descendants()->get();
        
        foreach ($descendants as $descendant) {
            $ids[] = $descendant->id;
        }
        
        return $ids;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Il nome dell\'unità è obbligatorio.',
            'name.max' => 'Il nome non può superare i 255 caratteri.',
            'type_id.required' => 'Il tipo di unità è obbligatorio.',
            'type_id.exists' => 'Il tipo di unità selezionato non è valido.',
            'parent_id.exists' => 'L\'unità parent selezionata non esiste.',
            'parent_id.not_in' => 'Un\'unità non può essere parent di se stessa.',
            'parent_uuid.exists' => 'L\'unità parent selezionata non esiste.',
            'parent_uuid.not_in' => 'Un\'unità non può essere parent di se stessa.',
            'code.max' => 'Il codice non può superare i 50 caratteri.',
            'description.max' => 'La descrizione non può superare i 1000 caratteri.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'type_id' => 'tipo',
            'parent_id' => 'unità parent',
            'parent_uuid' => 'unità parent',
            'code' => 'codice',
            'description' => 'descrizione',
        ];
    }
}

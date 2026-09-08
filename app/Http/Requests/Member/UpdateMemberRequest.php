<?php

namespace App\Http\Requests\Member;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $memberId = $this->route('member') ? $this->route('member')->id : null;

        $member = Member::find($memberId);

        // Safety check jika member tidak ditemukan
        if (!$member) {
            return [];
        }

        // 3. Tentukan aturan berdasarkan TIPE member (Parent vs Child)
        if ($member->parent_id == 0) {
            // --- ATURAN JIKA EDIT PARENT ---
            return [
                'nama'          => 'required|string',
                'alamat'        => 'nullable|string',
                'no_ktp'        => 'nullable|numeric',
                'no_hp'         => 'required|numeric',
                'tanggal_lahir' => 'required|date',
                'jenis_kelamin' => 'required|in:L,P',
                // 'membership'    => 'required|integer|exists:memberships,id',
                'image_profile' => 'nullable|mimes:png,jpg,jpeg', // Nullable agar tidak wajib re-upload
                // 'is_active'     => 'nullable|boolean',
                'rfid' => [
                    'nullable',
                    'string',
                    Rule::unique('members')->ignore($memberId),
                ],

                'rfid_group'    => 'nullable|array',
                'name_group'    => 'nullable|array',
                'image_group'   => 'nullable|array',

                'name_group.*'  => 'nullable|string',
                'image_group.*' => 'nullable|mimes:png,jpg,jpeg',

                'rfid_group.*' => [
                    'nullable',
                    'string',
                    'distinct',
                    Rule::unique('members', 'rfid')->where(function ($query) use ($memberId) {
                        return $query->where('parent_id', '!=', $memberId);
                    })
                ],
            ];
        } else {
            // --- ATURAN JIKA EDIT CHILD ---
            // Hanya validasi 3 field yang ada di form child
            return [
                'nama'          => 'required|string',
                'image_profile' => 'nullable|mimes:png,jpg,jpeg', // Nullable agar tidak wajib re-upload

                // Aturan 'unique' dengan 'ignore' untuk child
                'rfid' => [
                    'nullable',
                    'string',
                    Rule::unique('members')->ignore($memberId),
                ],
            ];
        }
    }
}

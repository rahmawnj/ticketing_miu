 <!-- Modal info membership -->
 <div class="modal fade" id="modal-dialog-membership">
     <div class="modal-dialog modal-lg">
         <div class="modal-content">
             <div class="modal-header">
                 <h4 class="modal-title">Info Membership</h4>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
             </div>
             <div class="modal-body">
                 <form action="" id="form-membership">
                     <div class="form-group mb-3">
                         <label for="membership-name" class="form-label">Nama</label>
                         <input type="text" name="name" id="membership-name" class="form-control" disabled>
                     </div>

                     <div class="form-group mb-3">
                         <label for="membership-id" class="form-label">Membership</label>
                         <select name="" id="membership-id" class="form-control">
                             @foreach ($memberships as $subs)
                             <option value="{{ $subs->id }}" data-max-person="{{ $subs->max_person }}">{{ $subs->name . " - " . $subs->max_person . " Person - Rp. " . $subs->price }}</option>
                             @endforeach
                         </select>
                     </div>
                 </form>
             </div>
             <div class="modal-footer">
                 <a href="javascript:;" id="btn-close" class="btn btn-white" data-bs-dismiss="modal">Close</a>
             </div>
         </div>
     </div>
 </div>
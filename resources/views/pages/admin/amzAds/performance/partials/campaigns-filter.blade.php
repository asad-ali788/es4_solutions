 <div class="offcanvas-body">
     <!-- Recommendation Filter Header -->
     <div class="form-group mb-2">
         <label for="rules" class="me-2 fw-semibold">Recommendation
             Filter:</label>
         @foreach ($ruleFilter as $rule)
             <div class="form-check mb-2">
                 <input class="form-check-input" type="checkbox" name="rules[]" value="{{ $rule->id }}"
                     id="rule_{{ $rule->id }}" {{ in_array($rule->id, request('rules', [])) ? 'checked' : '' }}>
                 <label class="form-check-label" for="rule_{{ $rule->id }}">
                     {{ $rule->action_label }}
                 </label>
             </div>
         @endforeach
     </div>
     <!-- ASIN Filter Header -->

     <div class="col-12 form-group mb-3">
         <label for="asins" class="me-2 fw-semibold">ASIN Filter:</label>
         <select name="asins[]" class="form-select asin-select" multiple="multiple" style="width: 100%;">
             @foreach ($filteredAsin as $asin)
                 <option value="{{ $asin }}" selected>
                     {{ $asin }}
                 </option>
             @endforeach
         </select>
     </div>
     <div class="row col-12">
         <div class="col-12 col-md-6 pe-0">
             <!-- CAMPAIGN STATE FILTER -->
             <div class="form-group mb-3">
                 <label for="campaign_state" class="me-2 fw-semibold">Campaign
                     State:</label>
                 <select name="campaign_state" id="campaign_state" class="form-select form-select">
                     <option value="">All</option>
                     <option value="enabled" {{ request('campaign_state') == 'enabled' ? 'selected' : '' }}>
                         Enabled</option>
                     <option value="paused" {{ request('campaign_state') == 'paused' ? 'selected' : '' }}>
                         Paused</option>
                 </select>
             </div>
         </div>
         <div class="col-12 col-md-6 pe-0">
             {{-- ACOS Filter --}}
             <div class="form-group mb-3">
                 <label for="acos" class="me-2 fw-semibold">ACOS (7d):</label>
                 <select name="acos" id="acos" class="form-select form-select">
                     <option value="">All</option>
                     <option value="0" {{ request('acos') == '0' ? 'selected' : '' }}>ACOS = 0
                     </option>
                     <option value="30" {{ request('acos') == '30' ? 'selected' : '' }}>ACOS ≤
                         30</option>
                     <option value="3045" {{ request('acos') == '3045' ? 'selected' : '' }}>ACOS
                         &gt; 30 & < 45</option>
                     <option value="45" {{ request('acos') == '45' ? 'selected' : '' }}>ACOS
                         ≥ 45</option>
                 </select>
             </div>
         </div>
     </div>
     <div class="row col-12">
         <div class="col-12 col-md-6 pe-0">
             {{-- Run Status Filter --}}
             <div class="form-group mb-3">
                 <label for="run_status" class="me-2 fw-semibold">Status:</label>
                 <select name="run_status" id="run_status" class="form-select form-select">
                     <option value="">All</option>
                     <option value="pending" {{ request('run_status') == 'pending' ? 'selected' : '' }}>
                         Pending
                     </option>
                     <option value="dispatched" {{ request('run_status') == 'dispatched' ? 'selected' : '' }}>
                         Dispatched</option>
                     <option value="failed" {{ request('run_status') == 'failed' ? 'selected' : '' }}>
                         Failed
                     </option>
                     <option value="done" {{ request('run_status') == 'done' ? 'selected' : '' }}>
                         Done
                     </option>
                 </select>
             </div>
         </div>
         <div class="col-12 col-md-6 pe-0">
             {{-- READY TO MAKE LIVE FILTER --}}
             <div class="form-group mb-3">
                 <label for="run_update" class="me-2 fw-semibold">Make
                     Live:</label>
                 <select name="run_update" id="run_update" class="form-select form-select">
                     <option value="">All</option>
                     <option value="1" {{ request('run_update') == '1' ? 'selected' : '' }}>
                         checked (Ready to Make Live)</option>
                     <option value="0" {{ request('run_update') == '0' ? 'selected' : '' }}>
                         not checked (Not Ready)
                     </option>
                 </select>
             </div>
         </div>
     </div>

     <div class="d-flex gap-2  mt-3">
         <button type="submit" class="btn btn-primary flex-grow-1">
             Apply Filters
         </button>
         <a class="btn btn-outline-secondary w-50" onclick="clearCampaignFilters()">
             <i class="mdi mdi-filter-remove"></i> Clear Filters
         </a>
     </div>
 </div>

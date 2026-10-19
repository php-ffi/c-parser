<?php

declare(strict_types=1);

namespace FFI\Parser\Preset;

/**
 * Target architecture of Clang, whose types {@see \FFI\Parser\Preset::clang()}
 * knows.
 *
 * @link https://github.com/llvm/llvm-project/blob/main/clang/include/clang/Basic/AArch64ACLETypes.def Clang, `AArch64ACLETypes.def`
 * @link https://github.com/llvm/llvm-project/blob/main/clang/include/clang/Basic/PPCTypes.def Clang, `PPCTypes.def`
 * @link https://github.com/llvm/llvm-project/blob/main/clang/include/clang/Basic/RISCVVTypes.def Clang, `RISCVVTypes.def`
 * @link https://github.com/llvm/llvm-project/blob/main/clang/include/clang/Basic/WebAssemblyReferenceTypes.def Clang, `WebAssemblyReferenceTypes.def`
 * @link https://github.com/llvm/llvm-project/blob/main/clang/include/clang/Basic/AMDGPUTypes.def Clang, `AMDGPUTypes.def`
 * @link https://github.com/llvm/llvm-project/blob/main/clang/include/clang/Basic/SPIRVTypes.def Clang, `SPIRVTypes.def`
 */
enum ClangTarget
{
    /**
     * Every architecture listed by the other cases.
     */
    case All;

    /**
     * AArch64: the NEON and SVE vectors, the SVE predicates and `__mfp8`.
     */
    case AArch64;

    /**
     * PowerPC: the MMA and VSX types.
     */
    case PowerPc;

    /**
     * RISC-V: the vectors of the V extension.
     */
    case RiscV;

    /**
     * WebAssembly: the reference types.
     */
    case WebAssembly;

    /**
     * AMDGPU: the resource and barrier types.
     */
    case AmdGpu;

    /**
     * SPIR-V: the event type.
     */
    case SpirV;

    /**
     * @var list<non-empty-string>
     */
    private const AARCH64_TYPEDEF_NAMES = [
        '__Int8x8_t',
        '__Int16x4_t',
        '__Int32x2_t',
        '__Uint8x8_t',
        '__Uint16x4_t',
        '__Uint32x2_t',
        '__Float16x4_t',
        '__Float32x2_t',
        '__Poly8x8_t',
        '__Poly16x4_t',
        '__Bfloat16x4_t',
        '__Int8x16_t',
        '__Int16x8_t',
        '__Int32x4_t',
        '__Int64x2_t',
        '__Uint8x16_t',
        '__Uint16x8_t',
        '__Uint32x4_t',
        '__Uint64x2_t',
        '__Float16x8_t',
        '__Float32x4_t',
        '__Float64x2_t',
        '__Poly8x16_t',
        '__Poly16x8_t',
        '__Poly64x2_t',
        '__Bfloat16x8_t',
        '__Mfloat8x8_t',
        '__Mfloat8x16_t',
        '__SVInt8_t',
        '__SVInt16_t',
        '__SVInt32_t',
        '__SVInt64_t',
        '__SVUint8_t',
        '__SVUint16_t',
        '__SVUint32_t',
        '__SVUint64_t',
        '__SVFloat16_t',
        '__SVFloat32_t',
        '__SVFloat64_t',
        '__SVBfloat16_t',
        '__SVMfloat8_t',
        '__clang_svint8x2_t',
        '__clang_svint16x2_t',
        '__clang_svint32x2_t',
        '__clang_svint64x2_t',
        '__clang_svuint8x2_t',
        '__clang_svuint16x2_t',
        '__clang_svuint32x2_t',
        '__clang_svuint64x2_t',
        '__clang_svfloat16x2_t',
        '__clang_svfloat32x2_t',
        '__clang_svfloat64x2_t',
        '__clang_svbfloat16x2_t',
        '__clang_svmfloat8x2_t',
        '__clang_svint8x3_t',
        '__clang_svint16x3_t',
        '__clang_svint32x3_t',
        '__clang_svint64x3_t',
        '__clang_svuint8x3_t',
        '__clang_svuint16x3_t',
        '__clang_svuint32x3_t',
        '__clang_svuint64x3_t',
        '__clang_svfloat16x3_t',
        '__clang_svfloat32x3_t',
        '__clang_svfloat64x3_t',
        '__clang_svbfloat16x3_t',
        '__clang_svmfloat8x3_t',
        '__clang_svint8x4_t',
        '__clang_svint16x4_t',
        '__clang_svint32x4_t',
        '__clang_svint64x4_t',
        '__clang_svuint8x4_t',
        '__clang_svuint16x4_t',
        '__clang_svuint32x4_t',
        '__clang_svuint64x4_t',
        '__clang_svfloat16x4_t',
        '__clang_svfloat32x4_t',
        '__clang_svfloat64x4_t',
        '__clang_svbfloat16x4_t',
        '__clang_svmfloat8x4_t',
        '__SVBool_t',
        '__clang_svboolx2_t',
        '__clang_svboolx4_t',
        '__SVCount_t',
        '__mfp8',
    ];

    /**
     * @var list<non-empty-string>
     */
    private const POWER_PC_TYPEDEF_NAMES = [
        '__dmr2048',
        '__dmr1024',
        '__vector_quad',
        '__vector_pair',
    ];

    /**
     * @var list<non-empty-string>
     */
    private const RISC_V_TYPEDEF_NAMES = [
        '__rvv_int8mf8_t',
        '__rvv_int8mf4_t',
        '__rvv_int8mf2_t',
        '__rvv_int8m1_t',
        '__rvv_int8m2_t',
        '__rvv_int8m4_t',
        '__rvv_int8m8_t',
        '__rvv_uint8mf8_t',
        '__rvv_uint8mf4_t',
        '__rvv_uint8mf2_t',
        '__rvv_uint8m1_t',
        '__rvv_uint8m2_t',
        '__rvv_uint8m4_t',
        '__rvv_uint8m8_t',
        '__rvv_int16mf4_t',
        '__rvv_int16mf2_t',
        '__rvv_int16m1_t',
        '__rvv_int16m2_t',
        '__rvv_int16m4_t',
        '__rvv_int16m8_t',
        '__rvv_uint16mf4_t',
        '__rvv_uint16mf2_t',
        '__rvv_uint16m1_t',
        '__rvv_uint16m2_t',
        '__rvv_uint16m4_t',
        '__rvv_uint16m8_t',
        '__rvv_int32mf2_t',
        '__rvv_int32m1_t',
        '__rvv_int32m2_t',
        '__rvv_int32m4_t',
        '__rvv_int32m8_t',
        '__rvv_uint32mf2_t',
        '__rvv_uint32m1_t',
        '__rvv_uint32m2_t',
        '__rvv_uint32m4_t',
        '__rvv_uint32m8_t',
        '__rvv_int64m1_t',
        '__rvv_int64m2_t',
        '__rvv_int64m4_t',
        '__rvv_int64m8_t',
        '__rvv_uint64m1_t',
        '__rvv_uint64m2_t',
        '__rvv_uint64m4_t',
        '__rvv_uint64m8_t',
        '__rvv_float16mf4_t',
        '__rvv_float16mf2_t',
        '__rvv_float16m1_t',
        '__rvv_float16m2_t',
        '__rvv_float16m4_t',
        '__rvv_float16m8_t',
        '__rvv_bfloat16mf4_t',
        '__rvv_bfloat16mf2_t',
        '__rvv_bfloat16m1_t',
        '__rvv_bfloat16m2_t',
        '__rvv_bfloat16m4_t',
        '__rvv_bfloat16m8_t',
        '__rvv_float32mf2_t',
        '__rvv_float32m1_t',
        '__rvv_float32m2_t',
        '__rvv_float32m4_t',
        '__rvv_float32m8_t',
        '__rvv_float64m1_t',
        '__rvv_float64m2_t',
        '__rvv_float64m4_t',
        '__rvv_float64m8_t',
        '__rvv_bool1_t',
        '__rvv_bool2_t',
        '__rvv_bool4_t',
        '__rvv_bool8_t',
        '__rvv_bool16_t',
        '__rvv_bool32_t',
        '__rvv_bool64_t',
        '__rvv_int8mf8x2_t',
        '__rvv_int8mf8x3_t',
        '__rvv_int8mf8x4_t',
        '__rvv_int8mf8x5_t',
        '__rvv_int8mf8x6_t',
        '__rvv_int8mf8x7_t',
        '__rvv_int8mf8x8_t',
        '__rvv_int8mf4x2_t',
        '__rvv_int8mf4x3_t',
        '__rvv_int8mf4x4_t',
        '__rvv_int8mf4x5_t',
        '__rvv_int8mf4x6_t',
        '__rvv_int8mf4x7_t',
        '__rvv_int8mf4x8_t',
        '__rvv_int8mf2x2_t',
        '__rvv_int8mf2x3_t',
        '__rvv_int8mf2x4_t',
        '__rvv_int8mf2x5_t',
        '__rvv_int8mf2x6_t',
        '__rvv_int8mf2x7_t',
        '__rvv_int8mf2x8_t',
        '__rvv_int8m1x2_t',
        '__rvv_int8m1x3_t',
        '__rvv_int8m1x4_t',
        '__rvv_int8m1x5_t',
        '__rvv_int8m1x6_t',
        '__rvv_int8m1x7_t',
        '__rvv_int8m1x8_t',
        '__rvv_int8m2x2_t',
        '__rvv_int8m2x3_t',
        '__rvv_int8m2x4_t',
        '__rvv_int8m4x2_t',
        '__rvv_uint8mf8x2_t',
        '__rvv_uint8mf8x3_t',
        '__rvv_uint8mf8x4_t',
        '__rvv_uint8mf8x5_t',
        '__rvv_uint8mf8x6_t',
        '__rvv_uint8mf8x7_t',
        '__rvv_uint8mf8x8_t',
        '__rvv_uint8mf4x2_t',
        '__rvv_uint8mf4x3_t',
        '__rvv_uint8mf4x4_t',
        '__rvv_uint8mf4x5_t',
        '__rvv_uint8mf4x6_t',
        '__rvv_uint8mf4x7_t',
        '__rvv_uint8mf4x8_t',
        '__rvv_uint8mf2x2_t',
        '__rvv_uint8mf2x3_t',
        '__rvv_uint8mf2x4_t',
        '__rvv_uint8mf2x5_t',
        '__rvv_uint8mf2x6_t',
        '__rvv_uint8mf2x7_t',
        '__rvv_uint8mf2x8_t',
        '__rvv_uint8m1x2_t',
        '__rvv_uint8m1x3_t',
        '__rvv_uint8m1x4_t',
        '__rvv_uint8m1x5_t',
        '__rvv_uint8m1x6_t',
        '__rvv_uint8m1x7_t',
        '__rvv_uint8m1x8_t',
        '__rvv_uint8m2x2_t',
        '__rvv_uint8m2x3_t',
        '__rvv_uint8m2x4_t',
        '__rvv_uint8m4x2_t',
        '__rvv_int16mf4x2_t',
        '__rvv_int16mf4x3_t',
        '__rvv_int16mf4x4_t',
        '__rvv_int16mf4x5_t',
        '__rvv_int16mf4x6_t',
        '__rvv_int16mf4x7_t',
        '__rvv_int16mf4x8_t',
        '__rvv_int16mf2x2_t',
        '__rvv_int16mf2x3_t',
        '__rvv_int16mf2x4_t',
        '__rvv_int16mf2x5_t',
        '__rvv_int16mf2x6_t',
        '__rvv_int16mf2x7_t',
        '__rvv_int16mf2x8_t',
        '__rvv_int16m1x2_t',
        '__rvv_int16m1x3_t',
        '__rvv_int16m1x4_t',
        '__rvv_int16m1x5_t',
        '__rvv_int16m1x6_t',
        '__rvv_int16m1x7_t',
        '__rvv_int16m1x8_t',
        '__rvv_int16m2x2_t',
        '__rvv_int16m2x3_t',
        '__rvv_int16m2x4_t',
        '__rvv_int16m4x2_t',
        '__rvv_uint16mf4x2_t',
        '__rvv_uint16mf4x3_t',
        '__rvv_uint16mf4x4_t',
        '__rvv_uint16mf4x5_t',
        '__rvv_uint16mf4x6_t',
        '__rvv_uint16mf4x7_t',
        '__rvv_uint16mf4x8_t',
        '__rvv_uint16mf2x2_t',
        '__rvv_uint16mf2x3_t',
        '__rvv_uint16mf2x4_t',
        '__rvv_uint16mf2x5_t',
        '__rvv_uint16mf2x6_t',
        '__rvv_uint16mf2x7_t',
        '__rvv_uint16mf2x8_t',
        '__rvv_uint16m1x2_t',
        '__rvv_uint16m1x3_t',
        '__rvv_uint16m1x4_t',
        '__rvv_uint16m1x5_t',
        '__rvv_uint16m1x6_t',
        '__rvv_uint16m1x7_t',
        '__rvv_uint16m1x8_t',
        '__rvv_uint16m2x2_t',
        '__rvv_uint16m2x3_t',
        '__rvv_uint16m2x4_t',
        '__rvv_uint16m4x2_t',
        '__rvv_int32mf2x2_t',
        '__rvv_int32mf2x3_t',
        '__rvv_int32mf2x4_t',
        '__rvv_int32mf2x5_t',
        '__rvv_int32mf2x6_t',
        '__rvv_int32mf2x7_t',
        '__rvv_int32mf2x8_t',
        '__rvv_int32m1x2_t',
        '__rvv_int32m1x3_t',
        '__rvv_int32m1x4_t',
        '__rvv_int32m1x5_t',
        '__rvv_int32m1x6_t',
        '__rvv_int32m1x7_t',
        '__rvv_int32m1x8_t',
        '__rvv_int32m2x2_t',
        '__rvv_int32m2x3_t',
        '__rvv_int32m2x4_t',
        '__rvv_int32m4x2_t',
        '__rvv_uint32mf2x2_t',
        '__rvv_uint32mf2x3_t',
        '__rvv_uint32mf2x4_t',
        '__rvv_uint32mf2x5_t',
        '__rvv_uint32mf2x6_t',
        '__rvv_uint32mf2x7_t',
        '__rvv_uint32mf2x8_t',
        '__rvv_uint32m1x2_t',
        '__rvv_uint32m1x3_t',
        '__rvv_uint32m1x4_t',
        '__rvv_uint32m1x5_t',
        '__rvv_uint32m1x6_t',
        '__rvv_uint32m1x7_t',
        '__rvv_uint32m1x8_t',
        '__rvv_uint32m2x2_t',
        '__rvv_uint32m2x3_t',
        '__rvv_uint32m2x4_t',
        '__rvv_uint32m4x2_t',
        '__rvv_int64m1x2_t',
        '__rvv_int64m1x3_t',
        '__rvv_int64m1x4_t',
        '__rvv_int64m1x5_t',
        '__rvv_int64m1x6_t',
        '__rvv_int64m1x7_t',
        '__rvv_int64m1x8_t',
        '__rvv_int64m2x2_t',
        '__rvv_int64m2x3_t',
        '__rvv_int64m2x4_t',
        '__rvv_int64m4x2_t',
        '__rvv_uint64m1x2_t',
        '__rvv_uint64m1x3_t',
        '__rvv_uint64m1x4_t',
        '__rvv_uint64m1x5_t',
        '__rvv_uint64m1x6_t',
        '__rvv_uint64m1x7_t',
        '__rvv_uint64m1x8_t',
        '__rvv_uint64m2x2_t',
        '__rvv_uint64m2x3_t',
        '__rvv_uint64m2x4_t',
        '__rvv_uint64m4x2_t',
        '__rvv_float16mf4x2_t',
        '__rvv_float16mf4x3_t',
        '__rvv_float16mf4x4_t',
        '__rvv_float16mf4x5_t',
        '__rvv_float16mf4x6_t',
        '__rvv_float16mf4x7_t',
        '__rvv_float16mf4x8_t',
        '__rvv_float16mf2x2_t',
        '__rvv_float16mf2x3_t',
        '__rvv_float16mf2x4_t',
        '__rvv_float16mf2x5_t',
        '__rvv_float16mf2x6_t',
        '__rvv_float16mf2x7_t',
        '__rvv_float16mf2x8_t',
        '__rvv_float16m1x2_t',
        '__rvv_float16m1x3_t',
        '__rvv_float16m1x4_t',
        '__rvv_float16m1x5_t',
        '__rvv_float16m1x6_t',
        '__rvv_float16m1x7_t',
        '__rvv_float16m1x8_t',
        '__rvv_float16m2x2_t',
        '__rvv_float16m2x3_t',
        '__rvv_float16m2x4_t',
        '__rvv_float16m4x2_t',
        '__rvv_float32mf2x2_t',
        '__rvv_float32mf2x3_t',
        '__rvv_float32mf2x4_t',
        '__rvv_float32mf2x5_t',
        '__rvv_float32mf2x6_t',
        '__rvv_float32mf2x7_t',
        '__rvv_float32mf2x8_t',
        '__rvv_float32m1x2_t',
        '__rvv_float32m1x3_t',
        '__rvv_float32m1x4_t',
        '__rvv_float32m1x5_t',
        '__rvv_float32m1x6_t',
        '__rvv_float32m1x7_t',
        '__rvv_float32m1x8_t',
        '__rvv_float32m2x2_t',
        '__rvv_float32m2x3_t',
        '__rvv_float32m2x4_t',
        '__rvv_float32m4x2_t',
        '__rvv_float64m1x2_t',
        '__rvv_float64m1x3_t',
        '__rvv_float64m1x4_t',
        '__rvv_float64m1x5_t',
        '__rvv_float64m1x6_t',
        '__rvv_float64m1x7_t',
        '__rvv_float64m1x8_t',
        '__rvv_float64m2x2_t',
        '__rvv_float64m2x3_t',
        '__rvv_float64m2x4_t',
        '__rvv_float64m4x2_t',
        '__rvv_bfloat16mf4x2_t',
        '__rvv_bfloat16mf4x3_t',
        '__rvv_bfloat16mf4x4_t',
        '__rvv_bfloat16mf4x5_t',
        '__rvv_bfloat16mf4x6_t',
        '__rvv_bfloat16mf4x7_t',
        '__rvv_bfloat16mf4x8_t',
        '__rvv_bfloat16mf2x2_t',
        '__rvv_bfloat16mf2x3_t',
        '__rvv_bfloat16mf2x4_t',
        '__rvv_bfloat16mf2x5_t',
        '__rvv_bfloat16mf2x6_t',
        '__rvv_bfloat16mf2x7_t',
        '__rvv_bfloat16mf2x8_t',
        '__rvv_bfloat16m1x2_t',
        '__rvv_bfloat16m1x3_t',
        '__rvv_bfloat16m1x4_t',
        '__rvv_bfloat16m1x5_t',
        '__rvv_bfloat16m1x6_t',
        '__rvv_bfloat16m1x7_t',
        '__rvv_bfloat16m1x8_t',
        '__rvv_bfloat16m2x2_t',
        '__rvv_bfloat16m2x3_t',
        '__rvv_bfloat16m2x4_t',
        '__rvv_bfloat16m4x2_t',
    ];

    /**
     * @var list<non-empty-string>
     */
    private const WEB_ASSEMBLY_TYPEDEF_NAMES = [
        '__externref_t',
    ];

    /**
     * @var list<non-empty-string>
     */
    private const AMD_GPU_TYPEDEF_NAMES = [
        '__amdgpu_buffer_rsrc_t',
        '__amdgpu_texture_t',
        '__amdgpu_named_workgroup_barrier_t',
        '__amdgpu_feature_predicate_t',
    ];

    /**
     * @var list<non-empty-string>
     */
    private const SPIR_V_TYPEDEF_NAMES = [
        '__spirv_event_t',
    ];

    /**
     * Lists the typedef names Clang declares for the architecture.
     *
     * @return list<non-empty-string>
     */
    public function getTypedefNames(): array
    {
        return match ($this) {
            self::All => self::getAllTypedefNames(),
            self::AArch64 => self::AARCH64_TYPEDEF_NAMES,
            self::PowerPc => self::POWER_PC_TYPEDEF_NAMES,
            self::RiscV => self::RISC_V_TYPEDEF_NAMES,
            self::WebAssembly => self::WEB_ASSEMBLY_TYPEDEF_NAMES,
            self::AmdGpu => self::AMD_GPU_TYPEDEF_NAMES,
            self::SpirV => self::SPIR_V_TYPEDEF_NAMES,
        };
    }

    /**
     * @return list<non-empty-string>
     */
    private static function getAllTypedefNames(): array
    {
        return [
            ...self::AARCH64_TYPEDEF_NAMES,
            ...self::POWER_PC_TYPEDEF_NAMES,
            ...self::RISC_V_TYPEDEF_NAMES,
            ...self::WEB_ASSEMBLY_TYPEDEF_NAMES,
            ...self::AMD_GPU_TYPEDEF_NAMES,
            ...self::SPIR_V_TYPEDEF_NAMES,
        ];
    }
}

# 0 "/tmp/tmp.F1CuKvZo4I/cjson.c"
# 0 "<built-in>"
# 0 "<command-line>"
# 1 "/usr/include/stdc-predef.h" 1 3 4
# 0 "<command-line>" 2
# 1 "/tmp/tmp.F1CuKvZo4I/cjson.c"
# 1 "/home/nesk/ffi-corpus/root/usr/include/cjson/cJSON.h" 1
# 86 "/home/nesk/ffi-corpus/root/usr/include/cjson/cJSON.h"
# 1 "/usr/lib/gcc/x86_64-linux-gnu/13/include/stddef.h" 1 3 4
# 145 "/usr/lib/gcc/x86_64-linux-gnu/13/include/stddef.h" 3 4

# 145 "/usr/lib/gcc/x86_64-linux-gnu/13/include/stddef.h" 3 4
typedef long int ptrdiff_t;
# 214 "/usr/lib/gcc/x86_64-linux-gnu/13/include/stddef.h" 3 4
typedef long unsigned int size_t;
# 329 "/usr/lib/gcc/x86_64-linux-gnu/13/include/stddef.h" 3 4
typedef int wchar_t;
# 425 "/usr/lib/gcc/x86_64-linux-gnu/13/include/stddef.h" 3 4
typedef struct {
  long long __max_align_ll __attribute__((__aligned__(__alignof__(long long))));
  long double __max_align_ld __attribute__((__aligned__(__alignof__(long double))));
# 436 "/usr/lib/gcc/x86_64-linux-gnu/13/include/stddef.h" 3 4
} max_align_t;
# 87 "/home/nesk/ffi-corpus/root/usr/include/cjson/cJSON.h" 2
# 103 "/home/nesk/ffi-corpus/root/usr/include/cjson/cJSON.h"

# 103 "/home/nesk/ffi-corpus/root/usr/include/cjson/cJSON.h"
typedef struct cJSON
{

    struct cJSON *next;
    struct cJSON *prev;

    struct cJSON *child;


    int type;


    char *valuestring;

    int valueint;

    double valuedouble;


    char *string;
} cJSON;

typedef struct cJSON_Hooks
{

      void *( *malloc_fn)(size_t sz);
      void ( *free_fn)(void *ptr);
} cJSON_Hooks;

typedef int cJSON_bool;
# 141 "/home/nesk/ffi-corpus/root/usr/include/cjson/cJSON.h"
const char* cJSON_Version(void);


void cJSON_InitHooks(cJSON_Hooks* hooks);



cJSON * cJSON_Parse(const char *value);
cJSON * cJSON_ParseWithLength(const char *value, size_t buffer_length);


cJSON * cJSON_ParseWithOpts(const char *value, const char **return_parse_end, cJSON_bool require_null_terminated);
cJSON * cJSON_ParseWithLengthOpts(const char *value, size_t buffer_length, const char **return_parse_end, cJSON_bool require_null_terminated);


char * cJSON_Print(const cJSON *item);

char * cJSON_PrintUnformatted(const cJSON *item);

char * cJSON_PrintBuffered(const cJSON *item, int prebuffer, cJSON_bool fmt);


cJSON_bool cJSON_PrintPreallocated(cJSON *item, char *buffer, const int length, const cJSON_bool format);

void cJSON_Delete(cJSON *item);


int cJSON_GetArraySize(const cJSON *array);

cJSON * cJSON_GetArrayItem(const cJSON *array, int index);

cJSON * cJSON_GetObjectItem(const cJSON * const object, const char * const string);
cJSON * cJSON_GetObjectItemCaseSensitive(const cJSON * const object, const char * const string);
cJSON_bool cJSON_HasObjectItem(const cJSON *object, const char *string);

const char * cJSON_GetErrorPtr(void);


char * cJSON_GetStringValue(const cJSON * const item);
double cJSON_GetNumberValue(const cJSON * const item);


cJSON_bool cJSON_IsInvalid(const cJSON * const item);
cJSON_bool cJSON_IsFalse(const cJSON * const item);
cJSON_bool cJSON_IsTrue(const cJSON * const item);
cJSON_bool cJSON_IsBool(const cJSON * const item);
cJSON_bool cJSON_IsNull(const cJSON * const item);
cJSON_bool cJSON_IsNumber(const cJSON * const item);
cJSON_bool cJSON_IsString(const cJSON * const item);
cJSON_bool cJSON_IsArray(const cJSON * const item);
cJSON_bool cJSON_IsObject(const cJSON * const item);
cJSON_bool cJSON_IsRaw(const cJSON * const item);


cJSON * cJSON_CreateNull(void);
cJSON * cJSON_CreateTrue(void);
cJSON * cJSON_CreateFalse(void);
cJSON * cJSON_CreateBool(cJSON_bool boolean);
cJSON * cJSON_CreateNumber(double num);
cJSON * cJSON_CreateString(const char *string);

cJSON * cJSON_CreateRaw(const char *raw);
cJSON * cJSON_CreateArray(void);
cJSON * cJSON_CreateObject(void);



cJSON * cJSON_CreateStringReference(const char *string);


cJSON * cJSON_CreateObjectReference(const cJSON *child);
cJSON * cJSON_CreateArrayReference(const cJSON *child);



cJSON * cJSON_CreateIntArray(const int *numbers, int count);
cJSON * cJSON_CreateFloatArray(const float *numbers, int count);
cJSON * cJSON_CreateDoubleArray(const double *numbers, int count);
cJSON * cJSON_CreateStringArray(const char *const *strings, int count);


cJSON_bool cJSON_AddItemToArray(cJSON *array, cJSON *item);
cJSON_bool cJSON_AddItemToObject(cJSON *object, const char *string, cJSON *item);



cJSON_bool cJSON_AddItemToObjectCS(cJSON *object, const char *string, cJSON *item);

cJSON_bool cJSON_AddItemReferenceToArray(cJSON *array, cJSON *item);
cJSON_bool cJSON_AddItemReferenceToObject(cJSON *object, const char *string, cJSON *item);


cJSON * cJSON_DetachItemViaPointer(cJSON *parent, cJSON * const item);
cJSON * cJSON_DetachItemFromArray(cJSON *array, int which);
void cJSON_DeleteItemFromArray(cJSON *array, int which);
cJSON * cJSON_DetachItemFromObject(cJSON *object, const char *string);
cJSON * cJSON_DetachItemFromObjectCaseSensitive(cJSON *object, const char *string);
void cJSON_DeleteItemFromObject(cJSON *object, const char *string);
void cJSON_DeleteItemFromObjectCaseSensitive(cJSON *object, const char *string);


cJSON_bool cJSON_InsertItemInArray(cJSON *array, int which, cJSON *newitem);
cJSON_bool cJSON_ReplaceItemViaPointer(cJSON * const parent, cJSON * const item, cJSON * replacement);
cJSON_bool cJSON_ReplaceItemInArray(cJSON *array, int which, cJSON *newitem);
cJSON_bool cJSON_ReplaceItemInObject(cJSON *object,const char *string,cJSON *newitem);
cJSON_bool cJSON_ReplaceItemInObjectCaseSensitive(cJSON *object,const char *string,cJSON *newitem);


cJSON * cJSON_Duplicate(const cJSON *item, cJSON_bool recurse);





cJSON_bool cJSON_Compare(const cJSON * const a, const cJSON * const b, const cJSON_bool case_sensitive);




void cJSON_Minify(char *json);



cJSON* cJSON_AddNullToObject(cJSON * const object, const char * const name);
cJSON* cJSON_AddTrueToObject(cJSON * const object, const char * const name);
cJSON* cJSON_AddFalseToObject(cJSON * const object, const char * const name);
cJSON* cJSON_AddBoolToObject(cJSON * const object, const char * const name, const cJSON_bool boolean);
cJSON* cJSON_AddNumberToObject(cJSON * const object, const char * const name, const double number);
cJSON* cJSON_AddStringToObject(cJSON * const object, const char * const name, const char * const string);
cJSON* cJSON_AddRawToObject(cJSON * const object, const char * const name, const char * const raw);
cJSON* cJSON_AddObjectToObject(cJSON * const object, const char * const name);
cJSON* cJSON_AddArrayToObject(cJSON * const object, const char * const name);




double cJSON_SetNumberHelper(cJSON *object, double number);


char* cJSON_SetValuestring(cJSON *object, const char *valuestring);
# 293 "/home/nesk/ffi-corpus/root/usr/include/cjson/cJSON.h"
void * cJSON_malloc(size_t size);
void cJSON_free(void *object);
# 2 "/tmp/tmp.F1CuKvZo4I/cjson.c" 2
